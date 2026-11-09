<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Migrasi ADITIF: galeri foto sertifikat yang diunggah admin (hanya foto). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $t) {
            $t->id();
            $t->string('image')->nullable();                                  // disk public: certificates/...
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
