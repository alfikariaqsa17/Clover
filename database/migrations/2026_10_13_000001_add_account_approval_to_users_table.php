<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Pendaftaran akun harus disetujui admin. Akun yang SUDAH ADA otomatis disetujui agar tidak terkunci. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('account_status', 10)->default('pending')->index();   // pending | approved | rejected
            $t->dateTime('approved_at')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->string('status_note')->nullable();
        });

        DB::table('users')->update(['account_status' => 'approved', 'approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['account_status', 'approved_at', 'approved_by', 'status_note']);
        });
    }
};
