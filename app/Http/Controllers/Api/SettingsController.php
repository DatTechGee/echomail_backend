<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function getSmtpSettings()
    {
        $userId = Auth::id();
        $smtp = UserSetting::get($userId, 'smtp', [
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'from_address' => '',
            'from_name' => '',
        ]);

        return ResponseHelper::success(1, 'SMTP settings retrieved', ['smtp' => $smtp]);
    }

    public function updateSmtpSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'required|in:tls,ssl,none',
            'from_address' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        $userId = Auth::id();
        $data = $request->only(['host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name']);

        // Don't save empty password (keep existing)
        if (empty($data['password'])) {
            $existing = UserSetting::get($userId, 'smtp', []);
            $data['password'] = $existing['password'] ?? '';
        }

        UserSetting::set($userId, 'smtp', $data);

        return ResponseHelper::success(1, 'SMTP settings saved', ['smtp' => $data]);
    }

    public function testSmtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'encryption' => 'required|in:tls,ssl,none',
            'from_address' => 'required|email',
            'to_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $transport = match ($request->encryption) {
                'ssl' => new \Swift_SmtpTransport($request->host, $request->port, 'ssl'),
                'none' => new \Swift_SmtpTransport($request->host, $request->port),
                default => new \Swift_SmtpTransport($request->host, $request->port, 'tls'),
            };

            $transport->setUsername($request->username);
            if ($request->password) {
                $transport->setPassword($request->password);
            }

            $mailer = new \Swift_Mailer($transport);
            $message = (new \Swift_Message('EchoMail SMTP Test'))
                ->setFrom($request->from_address, 'EchoMail')
                ->setTo($request->to_email)
                ->setBody('This is a test email from EchoMail. Your SMTP configuration is working correctly!');

            $mailer->send($message);

            return ResponseHelper::success(1, 'Test email sent successfully! Check your inbox.');
        } catch (\Exception $e) {
            return ResponseHelper::error(0, 'SMTP connection failed: ' . $e->getMessage());
        }
    }

    public function testSmtpConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'encryption' => 'required|in:tls,ssl,none',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $transport = match ($request->encryption) {
                'ssl' => new \Swift_SmtpTransport($request->host, $request->port, 'ssl'),
                'none' => new \Swift_SmtpTransport($request->host, $request->port),
                default => new \Swift_SmtpTransport($request->host, $request->port, 'tls'),
            };

            $transport->setUsername($request->username);
            if ($request->password) {
                $transport->setPassword($request->password);
            }

            $transport->start();
            $transport->stop();

            return ResponseHelper::success(1, 'SMTP connection successful!');
        } catch (\Exception $e) {
            return ResponseHelper::error(0, 'SMTP connection failed: ' . $e->getMessage());
        }
    }

    // API Keys
    public function listApiKeys()
    {
        $keys = ApiKey::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return ResponseHelper::success(1, 'API keys retrieved', ['api_keys' => $keys]);
    }

    public function createApiKey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        $key = ApiKey::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'key' => ApiKey::generateKey(),
            'permissions' => $request->permissions ?? ['read', 'write'],
            'active' => true,
        ]);

        return ResponseHelper::success(1, 'API key created', ['api_key' => $key], 201);
    }

    public function revokeApiKey(int $id)
    {
        $key = ApiKey::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$key) {
            return ResponseHelper::error(0, 'API key not found', [], 404);
        }

        $key->delete();

        return ResponseHelper::success(1, 'API key revoked');
    }

    public function toggleApiKey(int $id)
    {
        $key = ApiKey::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$key) {
            return ResponseHelper::error(0, 'API key not found', [], 404);
        }

        $key->update(['active' => !$key->active]);

        return ResponseHelper::success(1, 'API key ' . ($key->active ? 'activated' : 'deactivated'), ['api_key' => $key]);
    }
}
