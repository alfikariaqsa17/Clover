<?php

namespace App\Console\Commands;

use App\Support\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'clover:optimize-images {--dry-run : Hanya tampilkan foto yang besar, tanpa mengubah apa pun}';

    protected $description = 'Perkecil foto unggahan lama (maks 1600 px) dan buat salinan WebP berdampingan (serta foto kecil untuk galeri sertifikat) agar halaman lebih ringan. QRIS dan bukti pembayaran tidak disentuh. Nama file di database tidak berubah.';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $scanned = 0;
        $changed = 0;
        $saved = 0;
        $webp = 0;
        $thumbs = 0;

        foreach (Upload::OPTIMIZE_DIRS as $dir) {
            foreach ($disk->allFiles($dir) as $file) {
                if (! preg_match('/\.(jpe?g|png|webp)$/i', $file) || preg_match('/\.(jpe?g|png)\.webp$/i', $file) || str_contains($file, '.thumb.')) {
                    continue;   // bukan gambar, atau salinan WebP / foto kecil buatan sistem
                }
                $full = $disk->path($file);
                $before = (int) filesize($full);
                $scanned++;

                if ($this->option('dry-run')) {
                    if ($before > 400 * 1024) {
                        $this->line($file . ': ' . round($before / 1024) . ' KB');
                    }

                    continue;
                }
                if (Upload::optimizeFile($full, Upload::maxSide($dir))) {
                    clearstatcache(true, $full);
                    $saved += $before - (int) filesize($full);
                    $changed++;
                }
                if (! in_array($dir, Upload::NO_WEBP_DIRS, true) && Upload::makeWebp($full)) {
                    $webp++;
                }
                if ($dir === 'certificates' && Upload::makeThumb($full)) {
                    $thumbs++;
                    Upload::makeWebp($full . '.thumb.jpg');
                }
            }
        }

        $this->info($this->option('dry-run')
            ? "Dicek {$scanned} foto."
            : "Dicek {$scanned} foto: {$changed} diperkecil (hemat " . round($saved / 1024 / 1024, 1) . " MB), {$webp} salinan WebP dan {$thumbs} foto kecil sertifikat dibuat.");

        return self::SUCCESS;
    }
}
