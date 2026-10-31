<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Migration ADITIF: menambah kolom dan memindahkan data lama ke struktur baru.
 * Tidak ada tabel yang dihapus. Kolom lama hanya dibuang SETELAH datanya disalin.
 * WAJIB backup database/database.sqlite sebelum menjalankan.
 */
return new class extends Migration
{
    private function lightLevel(?string $text): string
    {
        $t = mb_strtolower((string) $text);
        if (str_contains($t, 'redup')) {
            return 'rendah';
        }
        if (str_contains($t, 'matahari penuh') || str_contains($t, 'terang')) {
            return str_contains($t, 'tidak langsung') ? 'sedang' : 'tinggi';
        }

        return 'sedang';
    }

    private function waterDays(?string $text): int
    {
        $t = mb_strtolower((string) $text);
        if (str_contains($t, '2 minggu')) {
            return 14;
        }
        if (str_contains($t, '3x')) {
            return 2;
        }
        if (str_contains($t, '2x') || str_contains($t, '1–2x') || str_contains($t, '1-2x')) {
            return 3;
        }

        return 7;
    }

    public function up(): void
    {
        $fk = DB::getDriverName() !== 'sqlite'; // FK pada ALTER TABLE hanya untuk MySQL/Postgres

        /* ---------- products ---------- */
        Schema::table('products', function (Blueprint $t) {
            $t->unsignedBigInteger('category_id')->nullable()->index();
            $t->unsignedBigInteger('plant_id')->nullable()->index();
            $t->unsignedInteger('sold_count')->default(0);
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_plant_of_week')->default(false);
            $t->boolean('is_active')->default(true)->index();
        });

        Schema::table('products', function (Blueprint $t) {
            $t->renameColumn('rating', 'rating_avg');
            $t->renameColumn('reviews', 'reviews_count');
        });

        $map = [
            'Indoor' => 'indoor', 'Outdoor' => 'outdoor', 'Tanaman Hias' => 'hias-daun',
            'Tanaman Mini' => 'meja-kerja', 'Tanaman Buah' => 'outdoor',
        ];
        $cats = DB::table('categories')->pluck('id', 'slug');
        $now = now();

        foreach (DB::table('products')->get() as $p) {
            $plantSlug = $p->slug;
            $n = 2;
            while (DB::table('plants')->where('slug', $plantSlug)->exists()) {
                $plantSlug = $p->slug . '-' . $n++;
            }
            $plantId = DB::table('plants')->insertGetId([
                'slug' => $plantSlug,
                'name' => $p->name,
                'light_level' => $this->lightLevel($p->light),
                'light_text' => $p->light,
                'care_level' => 'mudah',
                'water_days' => $this->waterDays($p->water),
                'water_text' => $p->water,
                'soil' => $p->media,
                'description' => $p->description,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('products')->where('id', $p->id)->update([
                'plant_id' => $plantId,
                'category_id' => $cats[$map[$p->category] ?? 'indoor'] ?? null,
            ]);
        }

        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn(['category', 'light', 'water', 'media']);
        });

        // Angka rating/ulasan lama adalah data contoh, bukan ulasan nyata -> dinolkan.
        DB::table('products')->update(['rating_avg' => 0, 'reviews_count' => 0]);

        /* ---------- services ---------- */
        Schema::table('services', function (Blueprint $t) {
            $t->unsignedSmallInteger('capacity_per_slot')->default(2);
            $t->boolean('is_active')->default(true);
        });

        /* ---------- articles ---------- */
        Schema::table('articles', function (Blueprint $t) {
            $t->unsignedBigInteger('article_category_id')->nullable()->index();
            $t->boolean('is_published')->default(true);
        });
        foreach (DB::table('articles')->get() as $a) {
            $slug = Str::slug($a->tag) ?: 'umum';
            $cid = DB::table('article_categories')->where('slug', $slug)->value('id')
                ?: DB::table('article_categories')->insertGetId(['slug' => $slug, 'name' => $a->tag, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('articles')->where('id', $a->id)->update(['article_category_id' => $cid]);
        }
        Schema::table('articles', function (Blueprint $t) {
            $t->dropColumn('tag');
        });

        /* ---------- reviews: moderasi + verifikasi pembelian ---------- */
        Schema::table('reviews', function (Blueprint $t) {
            $t->string('status', 10)->default('pending')->index();   // pending | approved | rejected
            $t->boolean('is_verified')->default(false);
        });
        DB::table('reviews')->update(['status' => 'pending', 'is_verified' => false]);

        /* ---------- bookings ---------- */
        Schema::table('bookings', function (Blueprint $t) {
            $t->unsignedBigInteger('service_id')->nullable()->index();
        });
        foreach (DB::table('services')->get() as $s) {
            DB::table('bookings')->where('service_name', $s->name)->update(['service_id' => $s->id]);
        }

        /* ---------- orders ---------- */
        Schema::table('orders', function (Blueprint $t) {
            $t->unsignedInteger('discount')->default(0);
            $t->string('coupon_code', 40)->nullable();
            $t->string('shipping_method', 20)->nullable();
            $t->index('status');
        });
        DB::table('orders')->where('status', 'Menunggu Konfirmasi')->update(['status' => 'Menunggu Pembayaran']);

        /* ---------- subscribers ---------- */
        Schema::table('subscribers', function (Blueprint $t) {
            $t->dateTime('consent_at')->nullable();
        });
        DB::table('subscribers')->whereNull('consent_at')->update(['consent_at' => $now]);

        /* ---------- users: akun admin & demo dianggap terverifikasi ---------- */
        DB::table('users')
            ->whereNull('email_verified_at')
            ->where(function ($q) {
                $q->where('role', 'admin')->orWhereIn('email', ['user@clover.id', 'rina@clover.id', 'budi@clover.id']);
            })
            ->update(['email_verified_at' => $now]);

        if ($fk) {
            Schema::table('products', function (Blueprint $t) {
                $t->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
                $t->foreign('plant_id')->references('id')->on('plants')->nullOnDelete();
            });
            Schema::table('articles', function (Blueprint $t) {
                $t->foreign('article_category_id')->references('id')->on('article_categories')->nullOnDelete();
            });
            Schema::table('bookings', function (Blueprint $t) {
                $t->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Kolom lama (category, light, water, media, tag) tidak dikembalikan otomatis.
        // Pulihkan dari backup database jika perlu.
        Schema::table('subscribers', fn (Blueprint $t) => $t->dropColumn('consent_at'));
    }
};
