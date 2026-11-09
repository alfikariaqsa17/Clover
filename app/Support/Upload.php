<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Semua unggahan memakai Laravel Storage (disk "public") -> storage/app/public, diakses via /storage.
 * Nilai lama tanpa folder (mis. "abc.jpg") berasal dari versi sebelumnya dan berada di public/uploads/{folder}.
 */
class Upload
{
    /** Folder foto katalog yang boleh dikecilkan otomatis. QRIS, bukti pembayaran, dan refund TIDAK disentuh. */
    public const OPTIMIZE_DIRS = ['products', 'banners', 'categories', 'plants', 'services', 'articles', 'reviews', 'returns', 'my-plants', 'site', 'photo', 'certificates'];

    /** Folder yang gambarnya ditampilkan lewat <img> langsung (bukan partial foto), jadi salinan WebP tidak berguna. */
    public const NO_WEBP_DIRS = ['reviews', 'returns'];

    /** Sisi terpanjang foto kecil (thumbnail) untuk galeri sertifikat. Foto penuh hanya dimuat saat diperbesar. */
    public const THUMB_SIDE = 900;

    /** Sisi terpanjang maksimum (px). Sertifikat memuat tulisan kecil, jadi diberi ruang lebih besar. */
    public static function maxSide(string $dir): int
    {
        return $dir === 'certificates' ? 2200 : 1600;
    }

    public static function save(UploadedFile $file, string $dir): string
    {
        $path = $file->store($dir, 'public');
        if ($path && in_array($dir, self::OPTIMIZE_DIRS, true)) {
            $full = Storage::disk('public')->path($path);
            self::optimizeFile($full, self::maxSide($dir));   // 1) kecilkan dimensi/ukuran, format asli tetap
            if (! in_array($dir, self::NO_WEBP_DIRS, true)) {
                self::makeWebp($full);   // 2) tambah salinan WebP berdampingan (dipakai browser yang mendukung)
            }
            if ($dir === 'certificates' && self::makeThumb($full)) {   // 3) foto kecil untuk galeri (+ WebP-nya)
                self::makeWebp($full . '.thumb.jpg');
            }
        }

        return $path;
    }

    /**
     * Muat gambar ke GD dengan orientasi yang benar. null bila tidak didukung atau tidak aman diproses.
     * GD membuang data EXIF; foto tegak dari HP memakai EXIF untuk orientasi, jadi diputar manual. Bila orientasi
     * tidak bisa dibaca dengan pasti, null dikembalikan agar foto asli dibiarkan (tidak berubah miring).
     *
     * @return array{0:\GdImage,1:int}|null [gambar, tipe IMAGETYPE_*]
     */
    private static function loadOriented(string $full): ?array
    {
        if (! is_file($full) || ! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $info = @getimagesize($full);
        if (! $info || $info[0] * $info[1] > 40000000) {   // terlalu besar untuk diproses aman di memori
            return null;
        }
        $type = $info[2];
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($full),
            IMAGETYPE_PNG => @imagecreatefrompng($full),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : false,
            default => false,
        };
        if (! $src) {
            return null;
        }

        if ($type === IMAGETYPE_JPEG) {
            if (! function_exists('exif_read_data')) {
                imagedestroy($src);

                return null;
            }
            $exif = @exif_read_data($full);
            $orientation = (int) (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1);
            if (in_array($orientation, [2, 4, 5, 7], true)) {   // cermin: tidak diproses
                imagedestroy($src);

                return null;
            }
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0) {
                $rotated = imagerotate($src, $angle, 0);
                if ($rotated) {
                    imagedestroy($src);
                    $src = $rotated;
                }
            }
        }

        return [$src, $type];
    }

    /**
     * Perkecil foto besar (maks. $max piksel di sisi terpanjang) dan kompres agar halaman cepat dimuat.
     * Foto dari HP bisa 3-5 MB; sebuah halaman dengan 12 foto itu terasa lag. TIDAK PERNAH menggagalkan unggahan:
     * bila ada yang tidak aman/tidak didukung, file asli dibiarkan.
     */
    public static function optimizeFile(string $full, int $max = 1600): bool
    {
        try {
            $info = is_file($full) ? @getimagesize($full) : false;
            if (! $info) {
                return false;
            }
            $size = (int) filesize($full);
            if ($info[0] <= $max && $info[1] <= $max && $size < 400 * 1024) {
                return false;   // sudah ringan
            }
            $loaded = self::loadOriented($full);
            if (! $loaded) {
                return false;
            }
            [$src, $type] = $loaded;

            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min(1, $max / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            if ($type !== IMAGETYPE_JPEG) {   // pertahankan transparansi PNG/WebP
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

            if ($type === IMAGETYPE_JPEG) {
                imageinterlace($dst, true);   // JPEG progresif: foto tampil bertahap, terasa lebih cepat di jaringan lambat
            }
            $tmp = $full . '.tmp';
            $ok = match ($type) {
                IMAGETYPE_JPEG => imagejpeg($dst, $tmp, 82),
                IMAGETYPE_PNG => imagepng($dst, $tmp, 6),
                IMAGETYPE_WEBP => imagewebp($dst, $tmp, 82),
                default => false,
            };
            imagedestroy($src);
            imagedestroy($dst);

            if ($ok && is_file($tmp) && filesize($tmp) > 0 && ($scale < 1 || filesize($tmp) < $size) && @rename($tmp, $full)) {
                return true;
            }
            @unlink($tmp);

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Buat salinan WebP BERDAMPINGAN dengan file asli ("foto.jpg" -> "foto.jpg.webp"). File asli tidak diubah/dihapus,
     * jadi browser lama, pratinjau media sosial, dan email tetap memakai JPG/PNG. Hanya dibuat bila lebih kecil.
     */
    public static function makeWebp(string $full, int $quality = 80): bool
    {
        try {
            if (! function_exists('imagewebp') || ! preg_match('/\.(jpe?g|png)$/i', $full) || ! is_file($full)) {
                return false;
            }
            $webp = $full . '.webp';
            if (is_file($webp) && filemtime($webp) >= filemtime($full)) {
                return false;   // sudah ada dan masih terbaru
            }
            $loaded = self::loadOriented($full);
            if (! $loaded) {
                return false;
            }
            [$src, $type] = $loaded;
            if (! imageistruecolor($src)) {
                imagepalettetotruecolor($src);   // PNG berpalet tidak didukung imagewebp
            }
            imagealphablending($src, false);
            imagesavealpha($src, true);

            $tmp = $webp . '.tmp';
            $ok = imagewebp($src, $tmp, $quality);
            imagedestroy($src);

            if ($ok && is_file($tmp) && filesize($tmp) > 0 && filesize($tmp) < filesize($full) && @rename($tmp, $webp)) {
                return true;
            }
            @unlink($tmp);

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Buat foto kecil BERDAMPINGAN dengan file asli ("foto.jpg" -> "foto.jpg.thumb.jpg") untuk galeri.
     * Galeri sertifikat menampilkan banyak foto sekaligus; memuat file penuh (hingga 2200 px) untuk kotak ~380 px
     * itu boros. File asli tidak diubah (dipakai saat diperbesar). TIDAK PERNAH menggagalkan unggahan.
     */
    public static function makeThumb(string $full, int $max = self::THUMB_SIDE, int $quality = 78): bool
    {
        try {
            if (! is_file($full)) {
                return false;
            }
            $thumb = $full . '.thumb.jpg';
            if (is_file($thumb) && filemtime($thumb) >= filemtime($full)) {
                return false;   // sudah ada dan masih terbaru
            }
            $info = @getimagesize($full);
            if (! $info || (max($info[0], $info[1]) <= $max && filesize($full) < 150 * 1024)) {
                return false;   // sudah kecil: pakai file aslinya
            }
            $loaded = self::loadOriented($full);
            if (! $loaded) {
                return false;
            }
            [$src] = $loaded;

            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min(1, $max / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // JPEG tanpa transparansi: latar putih
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imageinterlace($dst, true);

            $tmp = $thumb . '.tmp';
            $ok = imagejpeg($dst, $tmp, $quality);
            imagedestroy($src);
            imagedestroy($dst);

            if ($ok && is_file($tmp) && filesize($tmp) > 0 && filesize($tmp) < filesize($full) && @rename($tmp, $thumb)) {
                return true;
            }
            @unlink($tmp);

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** URL foto kecil bila ada; bila belum (foto lama), URL foto aslinya. Tampilan tidak berubah, hanya lebih ringan. */
    public static function thumbUrl(?string $path, string $legacyDir): ?string
    {
        $url = self::url($path, $legacyDir);
        if (! $url) {
            return null;
        }
        $has = str_contains($path, '/')
            ? Storage::disk('public')->exists($path . '.thumb.jpg')
            : is_file(public_path('uploads/' . $legacyDir . '/' . basename($path) . '.thumb.jpg'));

        return $has ? $url . '.thumb.jpg' : $url;
    }

    /** URL salinan WebP (untuk <picture>) bila filenya benar-benar ada; selain itu null. */
    public static function webpUrl(?string $url): ?string
    {
        if (! $url || str_contains($url, '?')) {
            return null;
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (preg_match('#/storage/(.+\.(?:jpe?g|png))$#i', $path, $m) && Storage::disk('public')->exists($m[1] . '.webp')) {
            return $url . '.webp';
        }
        if (preg_match('#/uploads/(.+\.(?:jpe?g|png))$#i', $path, $m) && is_file(public_path('uploads/' . $m[1] . '.webp'))) {
            return $url . '.webp';
        }

        return null;
    }

    public static function url(?string $path, string $legacyDir): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_contains($path, '/')) {
            return asset('storage/' . $path);
        }

        return asset('uploads/' . $legacyDir . '/' . $path);
    }

    public static function delete(?string $path, string $legacyDir): void
    {
        if (! $path) {
            return;
        }
        if (str_contains($path, '/')) {
            Storage::disk('public')->delete([$path, $path . '.webp', $path . '.thumb.jpg', $path . '.thumb.jpg.webp']);

            return;
        }
        $file = public_path('uploads/' . $legacyDir . '/' . basename($path));
        foreach ([$file, $file . '.webp', $file . '.thumb.jpg', $file . '.thumb.jpg.webp'] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }

    /** URL file bawaan di public/ bila ada (mis. images/products/monstera.jpg), selain itu null. */
    public static function bundled(string $relative): ?string
    {
        return is_file(public_path($relative)) ? asset($relative) : null;
    }
}
