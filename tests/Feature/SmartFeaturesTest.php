<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\Review;
use App\Models\User;
use App\Support\OrderWorkflow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test: pencarian pintar, ulasan berfoto, tanya-jawab, dan SEO. Jalankan: php artisan test --filter=SmartFeaturesTest */
class SmartFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'user'): User
    {
        $u = User::create([
            'name' => 'Tester ' . $role, 'email' => $role . uniqid() . '@test.id',
            'password' => Hash::make('password123'), 'role' => $role, 'phone' => '081234567890',
        ]);
        $u->forceFill(['account_status' => 'approved', 'approved_at' => now()])->save();

        return $u;
    }

    // ---------- Pencarian pintar ----------

    public function test_suggest_endpoint_returns_matching_products(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/cari/saran?q=monstera')->assertOk()
            ->assertJsonPath('products.0.name', Product::where('slug', 'monstera')->value('name'))
            ->assertJsonStructure(['products' => [['name', 'url', 'price', 'in_stock']], 'categories', 'did_you_mean']);
    }

    public function test_suggest_ignores_too_short_queries(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->getJson('/cari/saran?q=m')->assertOk()->assertJsonPath('products', []);
    }

    public function test_typo_gets_did_you_mean_suggestion(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/cari/saran?q=monsteraa')->assertOk()->assertJsonPath('did_you_mean', 'monstera');
        $this->get('/tanaman?q=monsteraa')->assertOk()->assertSee('Maksud Anda');
    }

    public function test_multi_word_search_and_rating_filter_work(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/tanaman?q=monstera')->assertOk()->assertSee('Monstera');
        $this->get('/tanaman?rating=4&urut=rating')->assertOk();
        $this->get('/tanaman?q=xyz-tidak-ada')->assertOk()->assertSee('Produk tidak ditemukan');
    }

    // ---------- Ulasan berfoto ----------

    private function completedOrderFor(User $user, Product $p): void
    {
        Mail::fake();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($user)->post('/checkout', [
            'recipient' => 'Budi', 'phone' => '081234567890', 'address' => 'Jl. Melati 1', 'city' => 'Bandung',
            'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'transfer',
        ]);
        $order = Order::latest('id')->first();
        OrderWorkflow::confirmPayment($order);
        OrderWorkflow::transition($order, 'Dikirim');
        OrderWorkflow::transition($order, 'Selesai');
    }

    public function test_review_can_include_photos(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->completedOrderFor($user, $p);

        $this->actingAs($user)->post("/tanaman/{$p->slug}/ulasan", [
            'rating' => 5, 'body' => 'Tanamannya segar dan packing aman sekali.',
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ])->assertSessionHasNoErrors();

        $review = Review::first();
        $this->assertCount(2, $review->photos);
        Storage::disk('public')->assertExists($review->photos[0]);
        $this->get("/tanaman/{$p->slug}")->assertOk()->assertSee('Foto ulasan');
    }

    public function test_review_rejects_more_than_three_photos_and_non_images(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->completedOrderFor($user, $p);

        $this->actingAs($user)->post("/tanaman/{$p->slug}/ulasan", [
            'rating' => 5, 'body' => 'Tanamannya segar dan packing aman sekali.',
            'photos' => array_fill(0, 4, UploadedFile::fake()->image('a.jpg')),
        ])->assertSessionHasErrors('photos');

        $this->actingAs($user)->post("/tanaman/{$p->slug}/ulasan", [
            'rating' => 5, 'body' => 'Tanamannya segar dan packing aman sekali.',
            'photos' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')],
        ])->assertSessionHasErrors('photos.0');
        $this->assertSame(0, Review::count());
    }

    // ---------- Tanya jawab ----------

    public function test_customer_asks_and_admin_answers_question(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $admin = $this->makeUser('admin');
        $p = Product::where('slug', 'monstera')->first();

        $this->actingAs($user)->post("/tanaman/{$p->slug}/tanya", ['question' => 'Apakah aman untuk kucing di rumah?'])->assertSessionHasNoErrors();
        $q = ProductQuestion::first();
        $this->assertNull($q->answer);
        $this->get("/tanaman/{$p->slug}")->assertSee('Apakah aman untuk kucing')->assertSee('Menunggu jawaban');

        $this->actingAs($admin)->post("/admin/tanya-jawab/{$q->id}/jawab", ['answer' => 'Monstera beracun bila dimakan, jauhkan dari hewan.'])->assertSessionHasNoErrors();
        $this->get("/tanaman/{$p->slug}")->assertSee('beracun bila dimakan');
        $this->assertNotNull($q->fresh()->answered_at);
    }

    public function test_hidden_question_is_not_public_and_guest_cannot_ask(): void
    {
        $this->seed(DatabaseSeeder::class);
        $p = Product::where('slug', 'monstera')->first();
        ProductQuestion::create(['product_id' => $p->id, 'question' => 'Pertanyaan yang disembunyikan admin', 'is_visible' => false]);

        $this->get("/tanaman/{$p->slug}")->assertOk()->assertDontSee('Pertanyaan yang disembunyikan admin');
        $this->post("/tanaman/{$p->slug}/tanya", ['question' => 'Tanpa login tidak boleh.'])->assertRedirect('/masuk');
    }

    public function test_question_rate_limit_per_day(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();

        for ($i = 1; $i <= 6; $i++) {
            $this->actingAs($user)->post("/tanaman/{$p->slug}/tanya", ['question' => "Pertanyaan nomor {$i} tentang produk ini"]);
        }
        $this->assertSame(5, ProductQuestion::count());
    }

    public function test_admin_questions_page_requires_admin(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->makeUser())->get('/admin/tanya-jawab')->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->get('/admin/tanya-jawab')->assertOk();
    }

    // ---------- SEO & PWA ----------

    public function test_product_page_has_seo_meta_and_structured_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $p = Product::where('slug', 'monstera')->first();

        $this->get("/tanaman/{$p->slug}")->assertOk()
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"priceCurrency":"IDR"', false);
    }

    public function test_private_pages_are_noindex_and_public_are_not(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs($this->makeUser())->get('/akun')->assertOk()->assertSee('noindex', false);
        $this->get('/tanaman')->assertOk()->assertDontSee('noindex', false);
    }

    public function test_sitemap_lists_products_and_pages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $res = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $res->headers->get('Content-Type'));
        $res->assertSee('<urlset', false)->assertSee('/tanaman/monstera', false)->assertSee(route('services'), false);
    }

    public function test_product_whatsapp_link_is_prefilled(): void
    {
        $this->seed(DatabaseSeeder::class);
        $p = Product::where('slug', 'monstera')->first();

        $this->get("/tanaman/{$p->slug}")->assertOk()->assertSee('wa.me/', false)->assertSee(rawurlencode('Halo Clover, saya tertarik dengan ' . $p->name), false);
    }
}
