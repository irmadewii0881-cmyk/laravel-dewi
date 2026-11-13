<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Shipping;
use App\Models\Cart;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Exception;

class CheckoutController extends Controller
{
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                // 1. Ambil customer dari token auth JWT
                $customer = auth()->guard('api')->user();

                // 2. Generate Nomor Invoice Unik
                $invoice_number = 'TRX-' . strtoupper(Str::random(10));

                // 3. Simpan Transaksi Utama
                $transaction = Transaction::create([
                    'customer_id'      => $customer->id,
                    'province_name'    => $request->province_name,
                    'city_name'        => $request->city_name,
                    'district_name'    => $request->district_name,
                    'subdistrict_name' => $request->subdistrict_name,
                    'zip_code'         => $request->zip_code,
                    'full_address'     => $request->full_address ?? $request->address,
                    'invoice'          => $invoice_number,
                    'weight'           => $request->weight,
                    'total'            => $request->total,
                    'status'           => 'pending',
                ]);

                // 4. SIMPAN DATA SHIPPING (Pengiriman) -> Ini yang sebelumnya hilang!
                Shipping::create([
                    'transaction_id'   => $transaction->id,
                    'shipping_courier' => $request->courier ?? $request->shipping_courier ?? 'JNT',
                    'shipping_service' => $request->service ?? $request->shipping_service ?? 'EZ',
                    'shipping_cost'    => $request->cost ?? $request->shipping_cost ?? $request->courier_cost ?? 0,
                ]);

                // 5. Pindahkan barang dari Cart ke TransactionDetail
                $carts = Cart::with('product')->where('customer_id', $customer->id)->get();

                foreach ($carts as $cart) {
                    TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'product_id'     => $cart->product_id,
                        'qty'            => $cart->qty,
                        'price'          => $cart->price ?? $cart->product->price,
                    ]);
                }

                // 6. Setup Midtrans Config
                Config::$serverKey    = config('midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
                Config::$isProduction = false;
                Config::$isSanitized  = true;
                Config::$is3ds        = true;

                // 7. Payload Snap Midtrans
                $params = [
                    'transaction_details' => [
                        'order_id'     => $transaction->invoice,
                        'gross_amount' => (int) $transaction->total,
                    ],
                    'customer_details' => [
                        'first_name' => $customer->name,
                        'email'      => $customer->email,
                    ],
                ];

                // 8. Request Snap Token
                $snapToken = Snap::getSnapToken($params);

                // 9. Update Snap Token ke Data Transaksi
                $transaction->update([
                    'snap_token' => $snapToken
                ]);

                // 10. Bersihkan Cart Customer
                Cart::where('customer_id', $customer->id)->delete();

                return response()->json([
                    'success'     => true,
                    'message'     => 'Checkout Berhasil!',
                    'snap_token'  => $snapToken,
                    'transaction' => $transaction
                ], 201);

            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal melakukan checkout',
                    'error'   => $e->getMessage()
                ], 500);
            }
        });
    }
}