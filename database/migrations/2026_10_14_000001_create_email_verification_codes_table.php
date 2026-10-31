<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verification_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('code_hash', 64);                    // HMAC-SHA256 dari OTP (OTP asli tidak disimpan)
            $t->dateTime('expires_at');
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->dateTime('sent_at')->nullable();            // terisi hanya jika email BERHASIL dikirim
            $t->dateTime('consumed_at')->nullable();        // terisi saat dipakai / digantikan / habis
            $t->timestamps();
            $t->index(['user_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');
    }
};
