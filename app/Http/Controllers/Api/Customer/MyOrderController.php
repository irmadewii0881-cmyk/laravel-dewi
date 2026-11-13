<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;

class MyOrderController extends Controller implements HasMiddleware
{
    /**
     * middleware
     *
     * @return array
     */
    public static function middleware(): array
    {
        return [
            'auth:api'
        ];
    }

    /**
     * index
     *
     * ✅ Ditambah eager load 'transactionDetails.product' supaya list order
     * di Flutter bisa nampilin foto produk & jumlah item tanpa perlu buka
     * detail order dulu (dipakai buat kartu order versi baru).
     *
     * @return void
     */
    public function index()
    {
        //get transactions
        $transactions = Transaction::query()
            ->with('customer', 'transactionDetails.product')
            ->where('customer_id', auth()->guard('api')->user()->id)
            ->latest()
            ->paginate(5);

        //return response JSON transactions
        return response()->json([
            'success' => true,
            'message' => 'List Pesanan: ' . auth()->guard('api')->user()->name,
            'data'    => $transactions
        ]);
    }

    /**
     * show
     *
     * @param mixed $snap_token
     * @return void
     */
    public function show($snap_token)
    {
        //get transaction (bisa cari via snap_token, invoice, atau id)
        $transaction = Transaction::query()
            ->with('customer', 'shipping', 'transactionDetails.product')
            ->where('customer_id', auth()->guard('api')->user()->id)
            ->where(function ($query) use ($snap_token) {
                $query->where('snap_token', $snap_token)
                      ->orWhere('invoice', $snap_token)
                      ->orWhere('id', $snap_token);
            })
            ->firstOrFail();

        //return response JSON transaction
        return response()->json([
            'success' => true,
            'message' => 'Detail Pesanan',
            'data'    => $transaction
        ]);
    }
}