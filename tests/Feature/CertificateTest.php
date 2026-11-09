<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test galeri sertifikat & legalitas: hanya foto: unggah via admin, tampil di beranda/tentang/halaman khusus. */
class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    private function makeUser(string $role = 'user'): User
    {
        $u = User::create([
            'name' => 'Tester ' . $role, 'email' => $role . uniqid() . '@test.id',
            'password' => Hash::make('password123'), 'role' => $role, 'phone' => '081234567890',
        ]);
        $u->forceFill(['account_status' => 'approved', 'approved_at' => now()])->save();

        return $u;
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'sort_order' => 0, 'is_active' => 1,
            'image' => UploadedFile::fake()->image('sertif.jpg', 1000, 700),
        ];
    }

    private function make(array $attr = []): Certificate
    {
        $path = 'certificates/' . uniqid() . '.jpg';
        Storage::disk('public')->put($path, 'x');

        return Certificate::create($attr + ['image' => $path, 'sort_order' => 0, 'is_active' => true]);
    }

    // ---------- admin ----------

    public function test_admin_uploads_certificate_photo(): void
    {
        $this->actingAs($this->makeUser('admin'))->post('/admin/certificates', $this->payload())->assertSessionHasNoErrors();

        $c = Certificate::first();
        $this->assertTrue($c->is_active);
        Storage::disk('public')->assertExists($c->image);
        $this->assertStringStartsWith('certificates/', $c->image);
    }

    public function test_photo_is_required_when_creating_but_optional_when_editing(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post('/admin/certificates', $this->payload(['image' => null]))->assertSessionHasErrors('image');
        $this->assertSame(0, Certificate::count());

        $c = $this->make();
        $old = $c->image;
        $this->actingAs($admin)->put("/admin/certificates/{$c->id}", $this->payload(['sort_order' => 5, 'image' => null]))->assertSessionHasNoErrors();

        $this->assertSame(5, $c->fresh()->sort_order);
        $this->assertSame($old, $c->fresh()->image);   // foto lama tetap dipakai
    }

    public function test_replacing_photo_deletes_the_old_file_and_deleting_removes_it(): void
    {
        $admin = $this->makeUser('admin');
        $c = $this->make();
        $old = $c->image;

        $this->actingAs($admin)->put("/admin/certificates/{$c->id}", $this->payload())->assertSessionHasNoErrors();
        $new = $c->fresh()->image;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);

        $this->actingAs($admin)->delete("/admin/certificates/{$c->id}");
        $this->assertSame(0, Certificate::count());
        Storage::disk('public')->assertMissing($new);
    }

    public function test_validation_rejects_bad_files(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('/admin/certificates', $this->payload(['image' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf')]))->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/certificates', $this->payload(['image' => UploadedFile::fake()->createWithContent('x.php', '<?php echo 1;')]))->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/certificates', $this->payload(['image' => UploadedFile::fake()->image('besar.jpg', 800, 800)->size(5000)]))->assertSessionHasErrors('image');
        $this->assertSame(0, Certificate::count());
    }

    public function test_only_admin_can_manage_certificates(): void
    {
        $user = $this->makeUser();
        $c = $this->make();

        $this->actingAs($user)->get('/admin/certificates')->assertForbidden();
        $this->actingAs($user)->post('/admin/certificates', $this->payload())->assertForbidden();
        $this->actingAs($user)->put("/admin/certificates/{$c->id}", $this->payload())->assertForbidden();
        $this->actingAs($user)->delete("/admin/certificates/{$c->id}")->assertForbidden();
        $this->assertSame(1, Certificate::count());

        $this->actingAs($this->makeUser('admin'))->get('/admin/certificates')->assertOk();
        $this->actingAs($this->makeUser('admin'))->get('/admin/certificates/create')->assertOk()->assertSee('data pribadi');
    }

    // ---------- tampilan publik ----------

    public function test_public_page_shows_only_active_certificates_with_photo(): void
    {
        $shown = $this->make();
        $hidden = $this->make(['is_active' => false]);
        $noPhoto = $this->make();
        $noPhoto->update(['image' => null]);

        $this->get('/sertifikat')->assertOk()
            ->assertSee(Storage::url($shown->image), false)
            ->assertDontSee(Storage::url($hidden->image), false)
            ->assertSee('data-cert', false);
        $this->assertSame(1, substr_count($this->get('/sertifikat')->getContent(), 'data-cert'));
    }

    public function test_certificates_are_ordered_by_admin_sort_order(): void
    {
        $c3 = $this->make(['sort_order' => 30]);
        $c1 = $this->make(['sort_order' => 1]);
        $c2 = $this->make(['sort_order' => 10]);

        $this->get('/sertifikat')->assertOk()->assertSeeInOrder([
            basename($c1->image), basename($c2->image), basename($c3->image),
        ], false);
    }

    public function test_empty_state_and_home_section_only_when_data_exists(): void
    {
        $this->get('/sertifikat')->assertOk()->assertSee('Belum ada dokumen');
        $this->get('/')->assertOk()->assertDontSee('Legalitas &amp; sertifikat kami', false);
        $this->get('/tentang-kami')->assertOk()->assertDontSee('Legalitas &amp; sertifikat', false);

        $this->make();
        $this->get('/')->assertOk()->assertSee('Legalitas &amp; sertifikat kami', false)->assertSee('data-cert', false);
        $this->get('/tentang-kami')->assertOk()->assertSee('Legalitas &amp; sertifikat', false)->assertSee('data-cert', false);
    }

    public function test_navigation_footer_and_sitemap_link_to_certificates(): void
    {
        $this->get('/')->assertOk()->assertSee(route('certificates'), false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/sertifikat', false);
    }

    // ---------- performa: foto kecil untuk galeri ----------

    public function test_large_photo_gets_a_light_thumbnail_and_gallery_uses_it(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('exif_read_data')) {
            $this->markTestSkipped('GD/EXIF tidak tersedia.');
        }
        $this->actingAs($this->makeUser('admin'))->post('/admin/certificates', $this->payload([
            'image' => UploadedFile::fake()->image('besar.jpg', 2000, 1400),
        ]))->assertSessionHasNoErrors();

        $c = Certificate::first();
        Storage::disk('public')->assertExists($c->image . '.thumb.jpg');
        $this->assertStringEndsWith('.thumb.jpg', $c->thumb_url);

        $html = $this->get('/sertifikat')->assertOk()->getContent();
        $this->assertStringContainsString($c->image . '.thumb.jpg', $html);   // galeri memakai foto kecil
        $this->assertStringContainsString('href="' . $c->image_url . '"', $html);   // perbesar tetap foto penuh

        $this->actingAs($this->makeUser('admin'))->delete("/admin/certificates/{$c->id}");
        Storage::disk('public')->assertMissing($c->image . '.thumb.jpg');
    }

    public function test_thumb_url_falls_back_to_original_for_old_photos(): void
    {
        $c = $this->make();   // tanpa foto kecil (data lama)
        $this->assertSame($c->image_url, $c->thumb_url);
    }
}
