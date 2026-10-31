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
    public static function save(UploadedFile $file, string $dir): string
    {
        return $file->store($dir, 'public');
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
            Storage::disk('public')->delete($path);

            return;
        }
        $file = public_path('uploads/' . $legacyDir . '/' . basename($path));
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /** URL file bawaan di public/ bila ada (mis. images/products/monstera.jpg), selain itu null. */
    public static function bundled(string $relative): ?string
    {
        return is_file(public_path($relative)) ? asset($relative) : null;
    }
}
