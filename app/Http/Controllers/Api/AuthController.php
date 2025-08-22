<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\RefreshToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Admin login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'rememberMe' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return ResponseHelper::error(0, 'Invalid credentials.', [], 401);
            }

            $user = User::where('email', $request->email)->first();

            if ($user->status === 'suspended') {
                return ResponseHelper::error(0, 'Your account is suspended. Please contact support.', [], 403);
            }

            if ($user->status === 'inactive') {
                return ResponseHelper::error(0, 'Your account is inactive. Please contact support.', [], 403);
            }

            // Check if 2FA is enabled
            if ($user->two_factor_enabled) {
                $twoFactorCode = $this->generateOtp();
                $user->two_factor_code = $twoFactorCode;
                $user->two_factor_expires_at = now()->addMinutes(10);
                $user->save();

                Mail::to($user->email)->send(new OtpMail($twoFactorCode));

                return ResponseHelper::success(
                    1,
                    'Two-factor authentication required. Please check your email.',
                    [
                        'email' => $user->email,
                        'requires_2fa' => true,
                        'user_id' => $user->uuid
                    ],
                    200
                );
            }

            $user->last_login_at = now();
            $user->save();

            $token = $user->createToken('EchoMail API Token')->plainTextToken;
            $refreshToken = $this->createRefreshToken($user);

            return ResponseHelper::success(
                1,
                'Login successful!',
                [
                    'user' => $this->formatUserData($user),
                    'access_token' => $token,
                    'refresh_token' => $refreshToken->token,
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Unable to Login: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to login! Please try again.', [], 500);
        }
    }

    /**
     * Verify two-factor authentication code
     */
    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = User::where('email', $request->email)
                        ->where('two_factor_code', $request->code)
                        ->where('two_factor_expires_at', '>', now())
                        ->first();

            if (!$user) {
                return ResponseHelper::error(0, 'Invalid or expired verification code.', [], 400);
            }

            $user->two_factor_code = null;
            $user->two_factor_expires_at = null;
            $user->last_login_at = now();
            $user->save();

            $token = $user->createToken('EchoMail API Token')->plainTextToken;
            $refreshToken = $this->createRefreshToken($user);

            return ResponseHelper::success(
                1,
                'Two-factor authentication successful!',
                [
                    'user' => $this->formatUserData($user),
                    'access_token' => $token,
                    'refresh_token' => $refreshToken->token,
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Unable to Verify 2FA: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to verify code! Please try again.', [], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        try {
            $user = Auth::user();

            if ($user) {
                $user->tokens()->delete();
                $user->refreshTokens()->update(['revoked' => true]);

                return ResponseHelper::success(1, 'Logout successful!', [], 200);
            }

            return ResponseHelper::error(0, 'Unable to logout due to invalid token.', [], 400);
        } catch (Exception $e) {
            Log::error('Unable to Logout: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to logout! Please try again.', [], 500);
        }
    }

    /**
     * Send reset password OTP to email
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return ResponseHelper::error(0, 'No user found with this email address.', [], 404);
            }

            $otp = $this->generateOtp();
            $user->otp = $otp;
            $user->otp_expires_at = now()->addMinutes(10);
            $user->save();

            Mail::to($user->email)->send(new OtpMail($otp));

            return ResponseHelper::success(
                1,
                'Password reset OTP sent to your email!',
                ['email' => $user->email],
                200
            );
        } catch (Exception $e) {
            Log::error('Unable to process Forgot Password: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to process forgot password request! Please try again.', [], 500);
        }
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8',
            'confirmPassword' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = User::where('email', $request->email)
                        ->where('otp', $request->otp)
                        ->where('otp_expires_at', '>', now())
                        ->first();

            if (!$user) {
                return ResponseHelper::error(0, 'Invalid or expired OTP.', [], 400);
            }

            $user->password = $request->password;
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();

            // Revoke all existing tokens for security
            $user->tokens()->delete();
            $user->refreshTokens()->update(['revoked' => true]);

            return ResponseHelper::success(1, 'Password reset successful!', [], 200);
        } catch (Exception $e) {
            Log::error('Unable to Reset Password: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to reset password! Please try again.', [], 500);
        }
    }

    /**
     * Refresh access token
     */
    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $refreshToken = RefreshToken::where('token', $request->refresh_token)
                                      ->where('revoked', false)
                                      ->where('expires_at', '>', now())
                                      ->first();

            if (!$refreshToken) {
                return ResponseHelper::error(0, 'Invalid or expired refresh token.', [], 401);
            }

            $user = $refreshToken->user;
            $newToken = $user->createToken('EchoMail API Token')->plainTextToken;
            $newRefreshToken = $this->createRefreshToken($user);

            // Revoke old refresh token
            $refreshToken->update(['revoked' => true]);

            return ResponseHelper::success(
                1,
                'Token refreshed successfully!',
                [
                    'access_token' => $newToken,
                    'refresh_token' => $newRefreshToken->token,
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Unable to Refresh Token: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to refresh token! Please try again.', [], 500);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'type' => 'required|in:reset,2fa',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return ResponseHelper::error(0, 'No user found with this email address.', [], 404);
            }

            $otp = $this->generateOtp();

            if ($request->type === '2fa') {
                $user->two_factor_code = $otp;
                $user->two_factor_expires_at = now()->addMinutes(10);
            } else {
                $user->otp = $otp;
                $user->otp_expires_at = now()->addMinutes(10);
            }

            $user->save();
            Mail::to($user->email)->send(new OtpMail($otp));

            return ResponseHelper::success(1, 'OTP sent to your email!', [], 200);
        } catch (Exception $e) {
            Log::error('Unable to Resend OTP: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to resend OTP! Please try again.', [], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function getUserProfile()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ResponseHelper::error(0, 'User not found.', [], 404);
            }

            return ResponseHelper::success(1, 'User profile retrieved successfully!', $this->formatUserData($user), 200);
        } catch (Exception $e) {
            Log::error('Unable to Get User Profile: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to retrieve user profile! Please try again.', [], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateUserProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName' => 'sometimes|string|min:2|max:50',
            'lastName' => 'sometimes|string|min:2|max:50',
            'phone' => 'sometimes|string|unique:users,phone,' . Auth::id(),
            'profileImage' => 'sometimes|string',
            'bio' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            if ($request->has('firstName')) {
                $user->first_name = $request->firstName;
            }

            if ($request->has('lastName')) {
                $user->last_name = $request->lastName;
            }

            if ($request->has('phone') && $user->phone !== $request->phone) {
                $user->phone = $request->phone;
            }

            if ($request->has('profileImage')) {
                $user->profile_image = $request->profileImage;
            }

            if ($request->has('bio')) {
                $user->bio = $request->bio;
            }

            $user->save();

            return ResponseHelper::success(
                1,
                'Profile updated successfully!',
                ['user' => $this->formatUserData($user)],
                200
            );
        } catch (Exception $e) {
            Log::error('Unable to Update User Profile: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to update profile! Please try again.', [], 500);
        }
    }

    /**
     * Change password (authenticated user)
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:8',
            'confirmPassword' => 'required|same:newPassword',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            if (!Hash::check($request->currentPassword, $user->password)) {
                return ResponseHelper::error(0, 'Current password is incorrect.', [], 400);
            }

            $user->password = $request->newPassword;
            $user->save();

            // Revoke all existing tokens for security
            $user->tokens()->delete();
            $user->refreshTokens()->update(['revoked' => true]);

            return ResponseHelper::success(1, 'Password changed successfully! Please login again.', [], 200);
        } catch (Exception $e) {
            Log::error('Unable to Change Password: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to change password! Please try again.', [], 500);
        }
    }

    /**
     * Toggle two-factor authentication
     */
    public function toggleTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'isEnabled' => 'required|boolean',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error(0, $validator->errors()->first(), $validator->errors()->all(), 400);
        }

        try {
            $user = Auth::user();

            if (!Hash::check($request->password, $user->password)) {
                return ResponseHelper::error(0, 'Password verification failed.', [], 400);
            }

            $user->two_factor_enabled = $request->isEnabled;
            $user->save();

            $message = $request->isEnabled ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.';

            return ResponseHelper::success(
                1,
                $message,
                ['two_factor_enabled' => $user->two_factor_enabled],
                200
            );
        } catch (Exception $e) {
            Log::error('Unable to Toggle 2FA: ' . $e->getMessage() . ' - Line no. ' . $e->getLine());
            return ResponseHelper::error(0, 'Unable to update two-factor authentication settings! Please try again.', [], 500);
        }
    }

    // HELPER METHODS

    private function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function createRefreshToken(User $user): RefreshToken
    {
        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(30),
        ]);
    }

    private function formatUserData(User $user): array
    {
        return [
            'uuid' => $user->uuid,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_image' => $user->profile_image,
            'bio' => $user->bio,
            'status' => $user->status,
            'two_factor_enabled' => $user->two_factor_enabled,
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
        ];
    }
}
