<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Migrasi ADITIF: foto pada ulasan dan tanya-jawab produk. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $t) {
            $t->json('photos')->nullable();
        });

        Schema::create('product_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('question');
            $t->text('answer')->nullable();
            $t->dateTime('answered_at')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->timestamps();
            $t->index(['product_id', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_questions');
        Schema::table('reviews', fn (Blueprint $t) => $t->dropColumn('photos'));
    }
};
