<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ✅ Kolom baru ini KHUSUS buat tracking status PENGIRIMAN/FULFILLMENT
     * (beda dari kolom 'status' yang lama, yang isinya status PEMBAYARAN
     * dari Midtrans: pending/success/dll). Admin (lewat Filament) yang
     * update kolom ini manual seiring pesanan diproses & dikirim.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('order_status', [
                'ordered',
                'processed',
                'on_delivery',
                'delivered',
            ])->default('ordered')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('order_status');
        });
    }
};