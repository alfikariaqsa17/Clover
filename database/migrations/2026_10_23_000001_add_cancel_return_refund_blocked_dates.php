<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration ADITIF (tidak ada tabel/kolom lama yang dihapus):
 * pembatalan pesanan, retur & refund, zona ongkir, tanggal blokir jasa, dan jadwal ulang booking.
 * Backup database/database.sqlite sebelum menjalankan: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->string('shipping_zone', 20)->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->string('cancel_reason')->nullable();
            $t->string('cancelled_by', 10)->nullable();   // customer | admin
        });

        Schema::table('payments', function (Blueprint $t) {
            $t->dateTime('refunded_at')->nullable();
        });

        Schema::table('bookings', function (Blueprint $t) {
            $t->unsignedTinyInteger('reschedule_count')->default(0);
            $t->date('previous_date')->nullable();
            $t->string('previous_time')->nullable();
            $t->dateTime('rescheduled_at')->nullable();
            $t->dateTime('cancelled_at')->nullable();
            $t->string('cancel_reason')->nullable();
        });

        Schema::create('blocked_dates', function (Blueprint $t) {
            $t->id();
            $t->date('date')->index();
            $t->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();   // null = semua layanan
            $t->string('reason', 150)->nullable();
            $t->timestamps();
        });

        Schema::create('order_returns', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('reason_code', 30);
            $t->text('description');
            $t->json('evidence')->nullable();
            $t->string('status', 20)->default('Diajukan')->index();
            $t->text('admin_note')->nullable();
            $t->boolean('needs_return')->default(false);
            $t->unsignedInteger('refund_amount')->nullable();
            $t->string('bank_name', 60)->nullable();
            $t->text('account_number')->nullable();        // terenkripsi (cast encrypted)
            $t->string('account_holder', 100)->nullable();
            $t->string('return_courier', 40)->nullable();
            $t->string('return_tracking', 80)->nullable();
            $t->boolean('restock')->default(false);
            $t->dateTime('decided_at')->nullable();
            $t->dateTime('shipped_back_at')->nullable();
            $t->dateTime('received_at')->nullable();
            $t->timestamps();
        });

        Schema::create('refunds', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_return_id')->nullable()->constrained('order_returns')->nullOnDelete();
            $t->string('source', 10);                      // cancel | return
            $t->unsignedInteger('amount');
            $t->string('reason')->nullable();
            $t->string('status', 20)->default('Menunggu Rekening')->index();
            $t->string('bank_name', 60)->nullable();
            $t->text('account_number')->nullable();        // terenkripsi (cast encrypted)
            $t->string('account_holder', 100)->nullable();
            $t->string('transfer_reference')->nullable();
            $t->string('proof_path')->nullable();
            $t->text('admin_note')->nullable();
            $t->dateTime('processed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('blocked_dates');
        Schema::table('bookings', function (Blueprint $t) {
            $t->dropColumn(['reschedule_count', 'previous_date', 'previous_time', 'rescheduled_at', 'cancelled_at', 'cancel_reason']);
        });
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn('refunded_at'));
        Schema::table('orders', function (Blueprint $t) {
            $t->dropColumn(['shipping_zone', 'cancelled_at', 'cancel_reason', 'cancelled_by']);
        });
    }
};
