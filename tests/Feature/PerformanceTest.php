<?php

namespace Tests\Feature;

use App\Support\Upload;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test perbaikan kecepatan: kompres foto saat unggah, perintah optimasi, header diagnosis, dan sakelar polling. */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** Isi file PNG sungguhan (dibuat langsung dengan GD, tidak bergantung pada file sementara yang bisa hilang). */
    private function pngBytes(int $w, int $h): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 40, 120, 70));
        ob_start();
        imagepng($im);
        $data = (string) ob_get_clean();
        imagedestroy($im);

        return $data;
    }

    private function dims(string $disk, string $path): array
    {
        $i = getimagesize(Storage::disk($disk)->path($path));

        return [$i[0], $i[1]];
    }

    public function test_large_catalog_photo_is_downscaled_on_upload(): void
    {
        Storage::fake('public');
        $path = Upload::save(UploadedFile::fake()->image('besar.png', 3000, 2000), 'products');

        [$w, $h] = $this->dims('public', $path);
        $this->assertSame(1600, $w);
        $this->assertSame(1067, $h);   // rasio 3:2 terjaga
        Storage::disk('public')->assertExists($path);
        $this->assertSame([], array_filter(Storage::disk('public')->allFiles(), fn ($f) => str_ends_with($f, '.tmp')));
    }

    public function test_small_photo_is_left_untouched(): void
    {
        Storage::fake('public');
        $path = Upload::save(UploadedFile::fake()->image('kecil.png', 800, 600), 'products');

        $this->assertSame([800, 600], $this->dims('public', $path));
    }

    public function test_qris_and_payment_images_are_never_recompressed(): void
    {
        Storage::fake('public');
        foreach (['qris', 'proofs', 'refunds'] as $dir) {
            $path = Upload::save(UploadedFile::fake()->image('x.png', 3000, 2000), $dir);
            $this->assertSame([3000, 2000], $this->dims('public', $path), "Folder {$dir} tidak boleh diubah");
            Storage::disk('public')->assertMissing($path . '.webp');
        }
    }

    public function test_jpeg_is_only_processed_when_orientation_can_be_read_safely(): void
    {
        Storage::fake('public');
        $path = Upload::save(UploadedFile::fake()->image('besar.jpg', 3000, 2000), 'products');

        [$w] = $this->dims('public', $path);
        // Tanpa ekstensi exif, JPEG dibiarkan asli (agar foto tegak dari HP tidak berubah miring).
        $this->assertSame(function_exists('exif_read_data') ? 1600 : 3000, $w);
    }

    public function test_corrupt_image_never_breaks_upload(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent('rusak.png', 'bukan gambar sungguhan');
        $path = Upload::save($file, 'products');

        Storage::disk('public')->assertExists($path);   // file tetap tersimpan apa adanya
    }

    public function test_optimize_command_shrinks_existing_photos_and_skips_private_folders(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $disk->put('products/lama.png', $this->pngBytes(3200, 2400));
        $disk->put('qris/qr.png', $this->pngBytes(3200, 2400));

        $this->artisan('clover:optimize-images')->assertSuccessful();

        $this->assertSame(1600, $this->dims('public', 'products/lama.png')[0]);
        $this->assertSame(3200, $this->dims('public', 'qris/qr.png')[0]);

        $this->artisan('clover:optimize-images', ['--dry-run' => true])->assertSuccessful();
    }

    public function test_webp_copy_is_created_next_to_original_and_original_is_kept(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD tanpa dukungan WebP.');
        }
        Storage::fake('public');
        $path = Upload::save(UploadedFile::fake()->image('foto.png', 1200, 800), 'products');

        Storage::disk('public')->assertExists($path);            // file asli tetap ada (fallback, pratinjau medsos, email)
        Storage::disk('public')->assertExists($path . '.webp');  // salinan WebP berdampingan
        $this->assertSame('image/webp', getimagesize(Storage::disk('public')->path($path . '.webp'))['mime']);
        $this->assertLessThan(filesize(Storage::disk('public')->path($path)), filesize(Storage::disk('public')->path($path . '.webp')));
    }

    public function test_photo_partial_uses_picture_only_when_webp_exists(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD tanpa dukungan WebP.');
        }
        Storage::fake('public');
        $with = Upload::save(UploadedFile::fake()->image('a.png', 1200, 800), 'products');
        $url = Upload::url($with, 'products');

        $html = view('partials.photo', ['src' => $url, 'alt' => 'Monstera'])->render();
        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertStringContainsString('srcset="' . $url . '.webp"', $html);
        $this->assertStringContainsString('src="' . $url . '"', $html);   // img asli tetap sebagai cadangan
        $this->assertStringContainsString('loading="lazy"', $html);

        $eager = view('partials.photo', ['src' => $url, 'alt' => 'x', 'eager' => true])->render();
        $this->assertStringNotContainsString('loading="lazy"', $eager);   // gambar utama tidak boleh lazy
        $this->assertStringContainsString('fetchpriority="high"', $eager);

        $plain = view('partials.photo', ['src' => 'http://localhost/storage/products/tanpa-webp.jpg', 'alt' => 'x'])->render();
        $this->assertStringNotContainsString('<picture>', $plain);
        $this->assertStringContainsString('loading="lazy"', $plain);
        $this->assertNull(Upload::webpUrl(asset('images/hero/bawaan.jpg')));
        $this->assertNull(Upload::webpUrl($url . '?v=1'));
    }

    public function test_deleting_an_image_also_deletes_its_webp_copy(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD tanpa dukungan WebP.');
        }
        Storage::fake('public');
        $path = Upload::save(UploadedFile::fake()->image('hapus.png', 1200, 800), 'products');
        Storage::disk('public')->assertExists($path . '.webp');

        Upload::delete($path, 'products');

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($path . '.webp');
    }

    public function test_command_creates_webp_copies_for_old_photos_without_touching_database_names(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD tanpa dukungan WebP.');
        }
        Storage::fake('public');
        $disk = Storage::disk('public');
        $disk->put('products/lama.png', $this->pngBytes(1200, 800));

        $this->artisan('clover:optimize-images')->assertSuccessful();

        $disk->assertExists('products/lama.png');         // nama & file lama tetap
        $disk->assertExists('products/lama.png.webp');    // salinan baru
        $this->artisan('clover:optimize-images')->assertSuccessful();   // dijalankan ulang aman (tidak membuat ganda)
        $this->assertSame(['products/lama.png', 'products/lama.png.webp'], collect($disk->allFiles('products'))->sort()->values()->all());
    }

    public function test_qris_amount_uses_site_display_font_not_an_unloaded_font(): void
    {
        $css = file_get_contents(public_path('css/style.css'));
        $this->assertStringNotContainsString('Playfair', $css);
        $this->assertMatchesRegularExpression('/\.qris-amount \{[^}]*var\(--font-display\)/', $css);
        $this->assertStringContainsString('.ph picture { display: contents; }', $css);
    }

    public function test_server_timing_header_only_in_debug_mode(): void
    {
        $this->seed(DatabaseSeeder::class);

        config(['app.debug' => true]);
        $res = $this->get('/')->assertOk();
        $this->assertStringContainsString('app;dur=', $res->headers->get('Server-Timing'));
        $this->assertMatchesRegularExpression('/\d+ query/', $res->headers->get('Server-Timing'));

        config(['app.debug' => false]);
        $this->get('/')->assertOk()->assertHeaderMissing('Server-Timing');
    }

    public function test_live_updates_can_be_switched_off_and_interval_is_configurable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = \App\Models\User::create(['name' => 'T', 'email' => 't@test.id', 'password' => bcrypt('password123'), 'role' => 'user', 'phone' => '081234567890']);
        $user->forceFill(['account_status' => 'approved', 'approved_at' => now()])->save();

        config(['clover.live.enabled' => true, 'clover.live.user_seconds' => 30]);
        $this->actingAs($user)->get('/akun')->assertOk()->assertSee('name="clover-live"', false)->assertSee('data-interval="30"', false);

        config(['clover.live.enabled' => false]);
        $this->actingAs($user)->get('/akun')->assertOk()->assertDontSee('name="clover-live"', false);
    }

    public function test_page_has_non_blocking_fonts_and_reveal_safety_net(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')->assertOk()
            ->assertSee('rel="preload" as="style"', false)
            ->assertSee("classList.add('js')", false)
            ->assertSee('reveal-all', false);
        $css = file_get_contents(public_path('css/style.css'));
        $this->assertStringContainsString('.js .reveal', $css);
        $this->assertStringContainsString('.reveal-all .reveal', $css);
        $this->assertStringContainsString('mod_expires', file_get_contents(public_path('.htaccess')));
    }
}
