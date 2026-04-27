<?php

namespace App\Http\Controllers\Api;

use App\Helper\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\PaymentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private const STATUS_COLORS = [
        'Pending'   => '#34C759',
        'Completed' => '#30D158',
        'Failed'    => '#FF3B30',
        'Cancelled' => '#8E8E93',
    ];

    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'recipient_email'     => 'required|email',
            'sender_name'         => 'required|string|max:100',
            'payment_description' => 'required|string|max:200',
            'amount'              => 'required|string|max:50',
            'sub_description'     => 'required|string|max:200',
            'datetime'            => 'required|string|max:100',
            'status'              => 'required|in:Pending,Completed,Failed,Cancelled',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error('Validation failed', $validator->errors(), 422);
        }

        $status      = $request->status;
        $statusColor = self::STATUS_COLORS[$status] ?? '#34C759';

        try {
            Mail::to($request->recipient_email)->send(new PaymentMail(
                senderName:         $request->sender_name,
                paymentDescription: $request->payment_description,
                amount:             $request->amount,
                subDescription:     $request->sub_description,
                datetime:           $request->datetime,
                status:             $status,
                statusColor:        $statusColor,
            ));

            return ResponseHelper::success('Payment notification sent successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to send email: ' . $e->getMessage(), null, 500);
        }
    }
}
