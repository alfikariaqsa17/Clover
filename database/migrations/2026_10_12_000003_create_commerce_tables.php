<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('alt')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_primary')->default(false);
            $t->timestamps();
        });

        // Foto produk lama (kolom products.image) dipindahkan ke tabel product_images lalu kolomnya dibuang.
        if (Schema::hasColumn('products', 'image')) {
            foreach (DB::table('products')->whereNotNull('image')->get(['id', 'name', 'image']) as $p) {
                DB::table('product_images')->insert([
                    'product_id' => $p->id, 'path' => $p->image, 'alt' => $p->name,
                    'sort_order' => 0, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            Schema::table('products', function (Blueprint $t) {
                $t->dropColumn('image');
            });
        }

        Schema::create('carts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique('user_id');
        });

        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('qty')->default(1);
            $t->timestamps();
            $t->unique(['cart_id', 'product_id']);
        });

        Schema::create('user_addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('label', 40);
            $t->string('recipient');
            $t->string('phone', 20);
            $t->text('address');
            $t->string('city');
            $t->string('postal_code', 10);
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('method', 20);                                   // transfer | cod | gateway
            $t->unsignedInteger('amount');
            $t->string('status', 12)->default('pending')->index();      // pending | paid | failed | refunded
            $t->string('reference')->nullable();
            $t->string('proof_path')->nullable();
            $t->dateTime('paid_at')->nullable();
            $t->timestamps();
            $t->unique('order_id');
        });

        Schema::create('shipments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('courier', 30);
            $t->string('service', 40)->nullable();
            $t->unsignedInteger('cost')->default(0);
            $t->string('tracking_number')->nullable();
            $t->string('status', 12)->default('pending');               // pending | shipped | delivered
            $t->dateTime('shipped_at')->nullable();
            $t->dateTime('delivered_at')->nullable();
            $t->timestamps();
            $t->unique('order_id');
        });
    }

    public function down(): void
    {
        foreach (['shipments', 'payments', 'user_addresses', 'cart_items', 'carts', 'product_images'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
