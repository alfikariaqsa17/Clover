<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi ADITIF untuk pembayaran QRIS GoPay (QRIS statis + bukti transaksi + verifikasi admin).
 * Tidak ada tabel/kolom lama yang dihapus. Backup database sebelum menjalankan: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $t) {
            $t->unsignedBigInteger('verified_by')->nullable();      // admin yang memverifikasi
            $t->dateTime('verified_at')->nullable();
            $t->string('rejection_reason')->nullable();             // alasan penolakan bukti terakhir
        });

        // Status baru "awaiting_verification" (21 huruf) melebihi varchar(12) lama. SQLite tidak membatasi panjang,
        // jadi perubahan hanya diperlukan di MySQL/MariaDB/PostgreSQL.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('payments', function (Blueprint $t) {
                $t->string('status', 30)->default('pending')->change();
            });
        }

        // Riwayat tiap bukti yang diunggah (audit): file privat, status tinjauan, dan alasan penolakan.
        Schema::create('payment_proofs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $t->string('path');                                     // disk "local" (privat): payment-proofs/{order_id}/...
            $t->string('mime', 40);
            $t->unsignedInteger('size');
            $t->string('note', 200)->nullable();                    // catatan pelanggan (opsional)
            $t->string('status', 12)->default('submitted');         // submitted | verified | rejected
            $t->string('reason')->nullable();                       // alasan penolakan
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
        Schema::table('payments', function (Blueprint $t) {
            $t->dropColumn(['verified_by', 'verified_at', 'rejection_reason']);
        });
    }
};
