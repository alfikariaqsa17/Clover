<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Support\Cart;
use App\Support\OrderWorkflow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Test fitur inti Clover. Jalankan: php artisan test
 * (Ditulis tanpa dapat dijalankan di lingkungan pembuat; jalankan di komputer Anda.)
 */
class CloverTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'user', bool $approved = true): User
    {
        $u = User::create([
            'name' => 'Tester ' . $role, 'email' => $role . uniqid() . '@test.id',
            'password' => Hash::make('password123'), 'role' => $role, 'phone' => '081234567890',
        ]);
        $u->forceFill([
            'account_status' => $approved ? 'approved' : 'pending',
            'approved_at' => $approved ? now() : null,
        ])->save();

        return $u;
    }

    private function checkoutPayload(array $extra = []): array
    {
        return $extra + [
            'recipient' => 'Budi Test', 'phone' => '081234567890', 'address' => 'Jl. Melati No. 1',
            'city' => 'Bandung', 'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'transfer',
        ];
    }

    public function test_home_and_catalog_pages_load(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->get('/')->assertOk()->assertSee('Clover');
        $this->get('/tanaman')->assertOk();
        $this->get('/tanaman?kategori=kaktus')->assertOk()->assertSee('Kaktus Mini');
        $this->get('/plant-finder?cahaya=rendah&pengalaman=pemula')->assertOk();
        $this->get('/kesehatan-tanaman')->assertOk();
    }

    public function test_unknown_page_uses_custom_404(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound();
    }

    public function test_customer_cannot_open_admin_but_admin_can(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->makeUser('user'))->get('/admin')->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->get('/admin')->assertOk();
    }

    public function test_guest_is_redirected_to_login_for_checkout_and_admin(): void
    {
        $this->get('/checkout')->assertRedirect('/masuk');
        $this->get('/admin')->assertRedirect('/masuk');
    }

    public function test_pending_account_cannot_use_customer_area(): void
    {
        $this->actingAs($this->makeUser('user', false))->get('/checkout')->assertRedirect('/masuk');
        $this->assertGuest();
    }

    public function test_cart_total_comes_from_database_prices(): void
    {
        $this->seed(DatabaseSeeder::class);
        $p = Product::where('slug', 'monstera')->first();
        $this->post('/keranjang', ['product_id' => $p->id, 'qty' => 2, 'price' => 1])->assertRedirect();
        $lines = Cart::lines();
        $this->assertSame($p->price * 2, $lines[0]['subtotal']);
    }

    public function test_checkout_creates_order_reduces_stock_and_ignores_tampered_total(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $stock = $p->stock;

        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 2]);
        $this->actingAs($user)->post('/checkout', $this->checkoutPayload(['total' => 1, 'subtotal' => 1]))->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame($p->price * 2, $order->subtotal);
        $this->assertSame($p->price * 2 + $order->shipping, $order->total);
        $this->assertSame('Menunggu Pembayaran', $order->status);
        $this->assertSame($stock - 2, $p->fresh()->stock);
        $this->assertNotNull($order->payment);
        $this->assertNotNull($order->shipment);
    }

    public function test_coupon_is_validated_and_applied_by_server(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'janda-bolong')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);

        $this->actingAs($user)->post('/checkout/kupon', ['code' => 'TIDAKADA'])->assertSessionHasErrors('code');
        $this->actingAs($user)->post('/checkout/kupon', ['code' => 'WELCOME10'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post('/checkout', $this->checkoutPayload())->assertRedirect();

        $order = Order::first();
        $this->assertSame('WELCOME10', $order->coupon_code);
        $this->assertSame(min(50000, intdiv($p->price * 10, 100)), $order->discount);
        $this->assertSame(1, Coupon::where('code', 'WELCOME10')->first()->used_count);
    }

    public function test_order_status_flow_and_cancel_restores_stock(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'aglaonema')->first();
        $stock = $p->stock;
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 3]);
        $this->actingAs($user)->post('/checkout', $this->checkoutPayload());
        $order = Order::first();

        $this->assertNotNull(OrderWorkflow::transition($order, 'Selesai'));          // loncat status ditolak
        $this->assertNull(OrderWorkflow::transition($order->fresh(), 'Dibatalkan'));
        $this->assertSame($stock, $p->fresh()->stock);
        $this->assertNotNull(OrderWorkflow::transition($order->fresh(), 'Diproses')); // pesanan batal tidak bisa dihidupkan
    }

    public function test_service_booking_blocks_full_slots(): void
    {
        $this->seed(DatabaseSeeder::class);
        $service = Service::where('slug', 'konsultasi-perawatan')->first();
        $service->update(['capacity_per_slot' => 1]);
        $date = now()->addDays(3)->toDateString();
        $payload = ['name' => 'Siti Test', 'whatsapp' => '081234567890', 'address' => 'Jl. Kenanga 5', 'date' => $date, 'time' => '08.00–10.00', 'area' => 20];

        $this->actingAs($this->makeUser())->post("/jasa/{$service->slug}/pesan", $payload)->assertRedirect();
        $this->assertSame(1, Booking::count());

        $this->actingAs($this->makeUser())->post("/jasa/{$service->slug}/pesan", $payload)->assertSessionHasErrors('time');
        $this->assertSame(1, Booking::count());
    }

    public function test_review_requires_completed_purchase(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'sansevieria')->first();

        $this->actingAs($user)->post("/tanaman/{$p->slug}/ulasan", ['rating' => 5, 'body' => 'Bagus sekali tanamannya'])->assertRedirect();
        $this->assertSame(0, $p->reviews()->count());

        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($user)->post('/checkout', $this->checkoutPayload(['payment_method' => 'cod']));
        $order = Order::first();
        OrderWorkflow::transition($order, 'Dikirim');
        OrderWorkflow::transition($order->fresh(), 'Selesai');

        $this->actingAs($user)->post("/tanaman/{$p->slug}/ulasan", ['rating' => 5, 'body' => 'Bagus sekali tanamannya'])->assertRedirect();
        $this->assertSame(1, $p->reviews()->count());
        $this->assertSame(1, $p->fresh()->reviews_count);
        $this->assertEquals(5.0, $p->fresh()->rating_avg);
    }

    public function test_customer_cannot_view_other_users_order(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($owner)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($owner)->post('/checkout', $this->checkoutPayload());
        $order = Order::first();

        $this->actingAs($other)->get("/akun/pesanan/{$order->number}")->assertNotFound();
        $this->actingAs($other)->get("/pesanan/{$order->number}")->assertForbidden();
    }

    public function test_registration_creates_pending_account_without_logging_in(): void
    {
        Mail::fake();
        $this->post('/daftar', ['name' => 'A', 'email' => 'x', 'password' => '123'])->assertSessionHasErrors(['name', 'email', 'phone', 'password']);

        $this->post('/daftar', [
            'name' => 'Rani Putri', 'email' => 'Rani@Test.ID', 'phone' => '081234567890',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'rani@test.id')->first();      // email disimpan huruf kecil
        $this->assertNotNull($user);
        $this->assertSame('user', $user->role);
        $this->assertSame('pending', $user->account_status);
        $this->assertGuest();                                          // belum masuk otomatis
        $this->get('/verifikasi-email')->assertOk()->assertSee('Verifikasi email Anda');
    }

    public function test_duplicate_email_in_different_case_is_rejected(): void
    {
        $existing = $this->makeUser('user');

        $this->post('/daftar', [
            'name' => 'Orang Lain', 'email' => strtoupper($existing->email), 'phone' => '081234567890',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    public function test_pending_account_cannot_login_but_approved_can(): void
    {
        Mail::fake();
        $pending = $this->makeUser('user', false);
        $this->post('/masuk', ['email' => $pending->email, 'password' => 'password123'])->assertRedirect(route('verification.notice'));
        $this->assertGuest();

        $approved = $this->makeUser('user', true);
        $this->post('/masuk', ['email' => $approved->email, 'password' => 'password123'])->assertRedirect();
        $this->assertAuthenticatedAs($approved);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $u = $this->makeUser('user');
        $this->post('/masuk', ['email' => $u->email, 'password' => 'salahsalah'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_only_admin_can_approve_and_then_user_can_login(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('user', true);
        $pending = $this->makeUser('user', false);

        $this->actingAs($customer)->post("/admin/users/{$pending->id}/setujui")->assertForbidden();
        $this->assertSame('pending', $pending->fresh()->account_status);

        $this->actingAs($admin)->post("/admin/users/{$pending->id}/setujui")->assertRedirect();
        $this->assertSame('approved', $pending->fresh()->account_status);
        $this->assertSame($admin->id, (int) $pending->fresh()->approved_by);

        auth()->logout();
        $this->post('/masuk', ['email' => $pending->email, 'password' => 'password123'])->assertRedirect();
        $this->assertAuthenticatedAs($pending);
    }

    public function test_rejected_account_cannot_login_and_sees_reason(): void
    {
        $admin = $this->makeUser('admin');
        $pending = $this->makeUser('user', false);

        $this->actingAs($admin)->post("/admin/users/{$pending->id}/tolak", ['note' => 'Data tidak lengkap'])->assertRedirect();
        $this->assertSame('rejected', $pending->fresh()->account_status);

        auth()->logout();
        $this->post('/masuk', ['email' => $pending->email, 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'Pendaftaran akun Anda ditolak. Alasan: Data tidak lengkap. Hubungi admin untuk bantuan.']);
    }

    public function test_approved_user_later_set_to_pending_is_logged_out_from_customer_area(): void
    {
        $user = $this->makeUser('user', true);
        $this->actingAs($user);
        $user->forceFill(['account_status' => 'pending'])->save();

        $this->get('/akun')->assertRedirect('/masuk');
        $this->assertGuest();
    }
}
