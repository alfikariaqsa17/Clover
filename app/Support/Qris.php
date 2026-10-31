<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/** Pengaturan QRIS merchant (disimpan di tabel settings, diatur admin tanpa mengedit kode). */
class Qris
{
    public const METHOD = 'qris_gopay';

    public static function imagePath(): ?string
    {
        return Setting::custom('qris_image');
    }

    public static function imageUrl(): ?string
    {
        $p = self::imagePath();

        return $p ? Upload::url($p, 'qris') : null;
    }

    /** Admin dapat menonaktifkan sementara tanpa menghapus gambar. Bawaan: aktif. */
    public static function enabled(): bool
    {
        return Setting::custom('qris_enabled') !== 'tidak';
    }

    public static function merchant(): string
    {
        return Setting::custom('qris_merchant') ?: 'Clover';
    }

    /**
     * Cara gambar mengisi kotak 3:4. Poster yang rasionya mendekati 3:4 diisi penuh ("cover", terpotong paling banyak
     * sekitar 6%) agar tidak ada tepi putih. Gambar lain (mis. QR persegi) tetap utuh ("contain"): memotongnya
     * dapat merusak kode QR sehingga tidak bisa dipindai.
     */
    public static function fit(): string
    {
        $p = self::imagePath();
        if (! $p || ! str_contains($p, '/')) {
            return 'contain';
        }
        try {
            $info = @getimagesize(Storage::disk('public')->path($p));
        } catch (\Throwable $e) {
            return 'contain';
        }
        if (! $info || empty($info[0]) || empty($info[1])) {
            return 'contain';
        }
        $ratio = $info[0] / $info[1];

        return ($ratio >= 0.70 && $ratio <= 0.80) ? 'cover' : 'contain';
    }

    /** QRIS dapat dipilih pelanggan hanya bila aktif DAN file gambarnya benar-benar ada. */
    public static function ready(): bool
    {
        $p = self::imagePath();
        if (! $p || ! self::enabled()) {
            return false;
        }

        return str_contains($p, '/') ? Storage::disk('public')->exists($p) : is_file(public_path('uploads/qris/' . basename($p)));
    }
}
