<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Aman dijalankan berulang: hanya MENAMBAH data yang belum ada dan tidak menimpa perubahan admin.
 * Data contoh pesanan/pemesanan ada di DemoSeeder (opsional, tidak ikut otomatis).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccountSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
