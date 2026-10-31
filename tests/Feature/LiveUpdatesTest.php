<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\Service;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\OrderWorkflow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Test: pembaruan otomatis (polling) admin & pelanggan. Jalankan: php artisan test --filter=LiveUpdatesTest */
class LiveUpdatesTest extends TestCase
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

    private function placeOrder(User $user): Order
    {
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($user)->post('/checkout', [
            'recipient' => 'Budi Test', 'phone' => '081234567890', 'address' => 'Jl. Melati No. 1', 'city' => 'Bandung',
            'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'transfer',
        ])->assertRedirect();

        return Order::latest('id')->first();
    }

    // ---------- akses ----------

    public function test_admin_live_endpoint_requires_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/admin/live')->assertUnauthorized();
        $this->actingAs($this->makeUser())->getJson('/admin/live')->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->getJson('/admin/live')->assertOk();
    }

    public function test_customer_live_endpoint_requires_login(): void
    {
        $this->getJson('/akun/live')->assertUnauthorized();
        $this->actingAs($this->makeUser())->getJson('/akun/live')->assertOk()->assertJsonStructure(['max', 'unread', 'new', 'sig']);
    }

    // ---------- pelanggan -> admin ----------

    public function test_first_poll_returns_baseline_without_new_events(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $this->placeOrder($this->makeUser());

        $res = $this->actingAs($admin)->getJson('/admin/live?init=1')->assertOk();
        $this->assertSame([], (array) $res->json('new'));
        $this->assertEquals(Order::max('id'), $res->json('max.orders'));
        $this->assertSame(1, $res->json('badges.orders'));
    }

    public function test_new_order_appears_for_admin_without_reload(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $base = $this->actingAs($admin)->getJson('/admin/live?init=1')->json();

        $order = $this->placeOrder($this->makeUser());

        $res = $this->actingAs($admin)->getJson('/admin/live?since[orders]=' . $base['max']['orders'])->assertOk();
        $res->assertJsonPath('new.orders.count', 1)->assertJsonPath('new.orders.title', 'Pesanan baru');
        $this->assertStringContainsString($order->number, $res->json('new.orders.text'));
        $this->assertSame(1, $res->json('badges.orders'));
        $this->assertNotSame($base['sig'], $res->json('sig'));

        // polling berikutnya dengan posisi terbaru: tidak ada kejadian ulang
        $again = $this->actingAs($admin)->getJson('/admin/live?since[orders]=' . $res->json('max.orders'))->assertOk();
        $this->assertSame([], (array) $again->json('new'));
        $this->assertSame($res->json('sig'), $again->json('sig'));
    }

    public function test_signature_changes_when_order_status_changes(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $order = $this->placeOrder($this->makeUser());
        $before = $this->actingAs($admin)->getJson('/admin/live?init=1')->json('sig');

        OrderWorkflow::confirmPayment($order);

        $after = $this->actingAs($admin)->getJson('/admin/live?init=1');
        $this->assertNotSame($before, $after->json('sig'));
        $this->assertSame(0, $after->json('badges.orders'));
    }

    public function test_payment_proof_upload_is_reported_to_admin(): void
    {
        Mail::fake();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $order = $this->placeOrder($user);
        $this->assertSame(0, $this->actingAs($admin)->getJson('/admin/live?init=1')->json('counts.proofs'));

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/bukti", ['proof' => UploadedFile::fake()->image('bukti.jpg')])->assertSessionHasNoErrors();

        $this->assertSame(1, $this->actingAs($admin)->getJson('/admin/live?init=1')->json('counts.proofs'));
    }

    public function test_booking_and_question_appear_for_admin(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $base = $this->actingAs($admin)->getJson('/admin/live?init=1')->json('max');

        $service = Service::first();
        $this->actingAs($user)->post("/jasa/{$service->slug}/pesan", [
            'name' => 'Budi', 'whatsapp' => '081234567890', 'address' => 'Jl. Melati 1, Bandung', 'area' => 20,
            'date' => now()->addDays(9)->toDateString(), 'time' => '08.00–10.00',
        ])->assertSessionHasNoErrors();
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post("/tanaman/{$p->slug}/tanya", ['question' => 'Apakah ukuran pot sudah termasuk?'])->assertSessionHasNoErrors();

        $q = 'since[bookings]=' . $base['bookings'] . '&since[questions]=' . $base['questions'];
        $res = $this->actingAs($admin)->getJson("/admin/live?{$q}")->assertOk();
        $res->assertJsonPath('new.bookings.count', 1)->assertJsonPath('new.questions.count', 1);
        $this->assertSame(1, $res->json('badges.bookings'));
        $this->assertSame(1, $res->json('badges.questions'));
        $this->assertSame(1, Booking::count());
        $this->assertSame(1, ProductQuestion::count());
    }

    // ---------- admin -> pelanggan ----------

    public function test_customer_gets_new_notification_when_admin_updates_order(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $order = $this->placeOrder($user);
        $base = $this->actingAs($user)->getJson('/akun/live?init=1')->json();

        OrderWorkflow::confirmPayment($order);

        $res = $this->actingAs($user)->getJson('/akun/live?since=' . $base['max'])->assertOk();
        $this->assertGreaterThan($base['unread'], $res->json('unread'));
        $this->assertNotEmpty($res->json('new'));
        $this->assertStringContainsString($order->number, $res->json('new.0.title'));
        $this->assertNotSame($base['sig'], $res->json('sig'));
    }

    public function test_customer_live_never_leaks_other_users_notifications(): void
    {
        $this->seed(DatabaseSeeder::class);
        $a = $this->makeUser();
        $b = $this->makeUser();
        UserNotification::create(['user_id' => $b->id, 'type' => 'order', 'title' => 'Rahasia milik B']);

        $res = $this->actingAs($a)->getJson('/akun/live?since=0')->assertOk();
        $this->assertSame(0, $res->json('unread'));
        $this->assertSame([], $res->json('new'));
        $this->assertStringNotContainsString('Rahasia milik B', $res->getContent());
    }

    // ---------- hook di halaman ----------

    public function test_admin_pages_include_live_hooks(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $this->placeOrder($this->makeUser());

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('name="clover-live"', false)
            ->assertSee('data-live="dash-cards"', false)
            ->assertSee('data-live="dash-recent"', false)
            ->assertSee('data-badge="orders"', false)
            ->assertSee('js/live.js', false);
        $this->actingAs($admin)->get('/admin/orders')->assertOk()->assertSee('data-live="list"', false)->assertSee('data-key="o', false);
    }

    public function test_customer_pages_include_live_hooks(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $order = $this->placeOrder($user);

        $this->actingAs($user)->get('/akun/pesanan')->assertOk()->assertSee('data-live="orders-page"', false)->assertSee('name="clover-live"', false)->assertSee('data-unread', false);
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}")->assertOk()->assertSee('data-live="order-page"', false);
        $this->actingAs($user)->get('/akun/notifikasi')->assertOk()->assertSee('data-live="notifs"', false);
    }

    public function test_guest_pages_have_no_live_hooks(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')->assertOk()->assertDontSee('name="clover-live"', false)->assertDontSee('js/live.js', false);
    }

    public function test_live_assets_exist(): void
    {
        $this->assertFileExists(public_path('js/live.js'));
        $this->assertFileExists(public_path('css/live.css'));
    }
}
