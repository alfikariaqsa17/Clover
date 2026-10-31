<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('icon', 40)->default('sprout');
            $t->string('image')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        $now = now();
        $base = [
            ['indoor', 'Tanaman Indoor', 'home', 1],
            ['outdoor', 'Tanaman Outdoor', 'sun', 2],
            ['sukulen', 'Sukulen', 'sprout', 3],
            ['kaktus', 'Kaktus', 'cactus', 4],
            ['hias-daun', 'Tanaman Hias Daun', 'leaf', 5],
            ['berbunga', 'Tanaman Berbunga', 'flower', 6],
            ['meja-kerja', 'Tanaman Meja Kerja', 'desk', 7],
            ['perlengkapan', 'Perlengkapan & Media Tanam', 'pot', 8],
        ];
        foreach ($base as [$slug, $name, $icon, $order]) {
            DB::table('categories')->insert([
                'slug' => $slug, 'name' => $name, 'icon' => $icon, 'sort_order' => $order,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Schema::create('plants', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('latin_name')->nullable();
            $t->string('light_level', 10)->default('sedang')->index();   // rendah | sedang | tinggi
            $t->string('light_text')->nullable();
            $t->string('care_level', 10)->default('mudah')->index();     // mudah | sedang | sulit
            $t->unsignedSmallInteger('water_days')->default(7);          // siram setiap N hari
            $t->string('water_text')->nullable();
            $t->string('humidity')->nullable();
            $t->string('temperature')->nullable();
            $t->string('soil')->nullable();
            $t->string('fertilizer')->nullable();
            $t->string('placements')->nullable();                        // "ruang tamu,kamar,kantor"
            $t->boolean('pet_safe')->nullable();
            $t->text('description')->nullable();
            $t->text('tips')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
        });

        Schema::create('article_categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20)->index();                             // announcement | promo | inspiration
            $t->string('title');
            $t->string('text')->nullable();
            $t->string('link_label')->nullable();
            $t->string('link_url')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_active')->default(true);
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('ends_at')->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('plant_problems', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('icon', 40)->default('leaf');
            $t->text('symptoms');
            $t->text('causes');
            $t->text('suggestions');
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('description')->nullable();
            $t->string('type', 10);                                      // percent | fixed
            $t->unsignedInteger('value');
            $t->unsignedInteger('min_subtotal')->default(0);
            $t->unsignedInteger('max_discount')->nullable();
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->dateTime('starts_at')->nullable();
            $t->dateTime('expires_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('amount');
            $t->timestamps();
            $t->unique('order_id');
        });
    }

    public function down(): void
    {
        foreach (['coupon_usages', 'coupons', 'plant_problems', 'banners', 'article_categories', 'plants', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
