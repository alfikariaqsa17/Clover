<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Untuk database yang sudah menjalankan versi lama tabel certificates
 * (dengan judul, penerbit, tahun, dst.): buang kolom teks yang tidak dipakai lagi.
 * Foto, urutan, dan status tampil TIDAK diubah. Pada instalasi baru migrasi ini tidak melakukan apa-apa.
 */
return new class extends Migration
{
    private array $old = ['type', 'title', 'issuer', 'year', 'credential_no', 'verify_url', 'description'];

    public function up(): void
    {
        // SQLite menolak menghapus kolom yang masih punya index, jadi index dihapus dulu.
        if (Schema::hasColumn('certificates', 'type') && $this->hasIndex('certificates_type_index')) {
            Schema::table('certificates', fn (Blueprint $t) => $t->dropIndex('certificates_type_index'));
        }

        $drop = array_values(array_filter($this->old, fn ($c) => Schema::hasColumn('certificates', $c)));
        if ($drop) {
            Schema::table('certificates', fn (Blueprint $t) => $t->dropColumn($drop));
        }
    }

    private function hasIndex(string $name): bool
    {
        foreach (Schema::getIndexes('certificates') as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }

    public function down(): void
    {
        // Data teks lama tidak dapat dipulihkan; tidak ada yang perlu dikembalikan.
    }
};
