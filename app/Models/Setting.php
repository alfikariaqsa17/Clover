<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    /** key => [label, default, type] */
    public const TEXTS = [
        'hero_eyebrow' => ['Teks kecil di atas judul hero', 'Botanical living, dirawat profesional', 'text'],
        'hero_title' => ['Judul besar hero', 'Hadirkan ruang hijau yang hidup', 'text'],
        'hero_subtitle' => ['Subjudul hero', 'Merawat Taman, Menyemai Kehidupan', 'text'],
        'hero_desc' => ['Deskripsi hero', 'Kami menyediakan tanaman pilihan dan jasa perawatan taman untuk membantu menciptakan ruang hijau yang indah, sehat, dan nyaman.', 'textarea'],
        'cta_title' => ['Judul banner konsultasi', 'Tanaman Anda butuh perawatan?', 'text'],
        'cta_desc' => ['Deskripsi banner CTA', 'Percayakan perawatan tanaman dan taman Anda kepada CLOVER.', 'text'],
        'whatsapp' => ['Nomor WhatsApp', '+62 812-3456-7890', 'text'],
        'email' => ['Email', 'hello@clover.id', 'text'],
        'address' => ['Alamat', 'Jl. Melati No.12, Bandung', 'text'],
        'hours' => ['Jam operasional', 'Senin–Minggu, 08.00–17.00', 'text'],
    ];

    /** key => [label, default file (public/), petunjuk] */
    public const IMAGES = [
        'hero_bg' => ['Foto utama Hero (beranda)', 'images/hero/hero-clover.jpg', 'Foto tegak/persegi berkualitas tinggi, min. 1200 px'],
        'cta_bg' => ['Background banner konsultasi', 'images/about/cta.jpg', 'Foto lebar, min. 1400 px'],
        'newsletter_bg' => ['Background bagian newsletter', 'images/about/newsletter.jpg', 'Foto lebar, min. 1200 px'],
        'about_bg' => ['Foto halaman Tentang Kami', 'images/about/story.jpg', 'Foto lebar 16:9'],
        'auth_bg' => ['Foto halaman Masuk & Daftar', 'images/about/auth.jpg', 'Foto tegak/persegi'],
    ];

    private static ?array $cache = null;

    private static function values(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = self::query()->pluck('value', 'name')->all();
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }

        return self::$cache;
    }

    public static function get(string $key): string
    {
        $v = self::values()[$key] ?? null;
        if ($v !== null && $v !== '') {
            return (string) $v;
        }

        return (string) (self::TEXTS[$key][1] ?? '');
    }

    public static function put(string $key, ?string $value): void
    {
        self::updateOrCreate(['name' => $key], ['value' => $value]);
        self::$cache = null;
    }

    public static function img(string $key): ?string
    {
        $v = self::values()[$key] ?? null;

        $url = \App\Support\Upload::url($v ?: null, 'site');

        return $url ?: \App\Support\Upload::bundled(self::IMAGES[$key][1]);
    }

    public static function custom(string $key): ?string
    {
        $v = self::values()[$key] ?? null;

        return $v ?: null;
    }

    /** Nomor WhatsApp hanya angka (format 62...) untuk link wa.me */
    public static function wa(): string
    {
        $d = preg_replace('/\D/', '', self::get('whatsapp'));
        if (str_starts_with($d, '0')) {
            $d = '62' . substr($d, 1);
        }

        return $d;
    }
}
