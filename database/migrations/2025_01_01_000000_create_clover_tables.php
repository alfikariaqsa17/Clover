<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('category');
            $t->unsignedInteger('price');
            $t->decimal('rating', 2, 1)->default(4.5);
            $t->unsignedInteger('reviews')->default(0);
            $t->unsignedInteger('stock')->default(0);
            $t->string('size');
            $t->string('light');
            $t->string('water');
            $t->string('media');
            $t->text('description');
            $t->string('image')->nullable();
            $t->timestamps();
        });

        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('icon', 16);
            $t->text('description');
            $t->unsignedInteger('price');
            $t->string('estimate');
            $t->string('schedule');
            $t->json('includes');
            $t->string('image')->nullable();
            $t->timestamps();
        });

        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('tag');
            $t->text('excerpt');
            $t->text('body');
            $t->date('published_at');
            $t->string('image')->nullable();
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('number')->unique();
            $t->string('name');
            $t->string('whatsapp');
            $t->string('address');
            $t->string('city');
            $t->string('postal_code', 10);
            $t->text('notes')->nullable();
            $t->unsignedInteger('subtotal');
            $t->unsignedInteger('shipping');
            $t->unsignedInteger('total');
            $t->string('status')->default('Menunggu Konfirmasi');
            $t->timestamps();
        });

        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->string('name');
            $t->unsignedInteger('price');
            $t->unsignedInteger('qty');
            $t->timestamps();
        });

        Schema::create('bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('whatsapp');
            $t->string('address');
            $t->string('service_name');
            $t->date('date');
            $t->string('time');
            $t->unsignedInteger('area');
            $t->text('notes')->nullable();
            $t->string('status')->default('Menunggu Konfirmasi');
            $t->timestamps();
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('whatsapp');
            $t->string('subject');
            $t->text('body');
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });

        Schema::create('subscribers', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'product_id']);
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->text('body');
            $t->timestamps();
            $t->unique(['user_id', 'product_id']);
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('value')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'reviews', 'wishlists', 'subscribers', 'messages', 'bookings', 'order_items', 'orders', 'articles', 'services', 'products'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
