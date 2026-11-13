<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CallbackController extends Controller
{
    // Tambahkan ini agar Callback bisa diakses tanpa Token Auth
    public function __construct()
    {
        // Kosongkan middleware (bypass auth)
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $payload      = $request->getContent();
        $notification = json_decode($payload);

        // Jika dipanggil dari Postman tanpa payload lengkap, beri response aman
        if (!$notification || !isset($notification->order_id)) {
            return response()->json(['message' => 'Invalid notification payload'], 400);
        }

        // 1. Bypass / Validasi Signature Key
        $validSignatureKey = hash("sha512", $notification->order_id . $notification->status_code . $notification->gross_amount . config('midtrans.server_key'));

        if (isset($notification->signature_key) && $notification->signature_key != $validSignatureKey) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transactionStatus = $notification->transaction_status;
        $type              = $notification->payment_type ?? '';
        $orderId           = $notification->order_id;

        // 2. Cari Data Transaksi
        $data_transaction = Transaction::where('invoice', $orderId)->first();

        if (!$data_transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // 3. Update Status
        if ($transactionStatus == 'capture') {
            if ($type == 'credit_card') {
                $data_transaction->update(['status' => 'success']);
            }
        } elseif ($transactionStatus == 'settlement') {
            $data_transaction->update(['status' => 'success']);
        } elseif ($transactionStatus == 'pending') {
            $data_transaction->update(['status' => 'pending']);
        } elseif ($transactionStatus == 'deny') {
            $data_transaction->update(['status' => 'failed']);
        } elseif ($transactionStatus == 'expire') {
            $data_transaction->update(['status' => 'expired']);
        } elseif ($transactionStatus == 'cancel') {
            $data_transaction->update(['status' => 'failed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification processed successfully'
        ], 200);
    }
}