<?php

namespace Tests\Feature;

use App\Mail\NoticeMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\ReceiptMail;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Service;
use App\Models\User;
use App\Support\CheckoutService;
use App\Support\OrderWorkflow;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Test: ongkir per zona, email konfirmasi & kuitansi, pembatalan, retur & refund, tanggal blokir, jadwal ulang.
 * Jalankan: php artisan test --filter=CommerceFlowTest
 */
class CommerceFlowTest extends TestCase
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

    private function payload(array $extra = []): array
    {
        return $extra + [
            'recipient' => 'Budi Test', 'phone' => '081234567890', 'address' => 'Jl. Melati No. 1',
            'city' => 'Bandung', 'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'transfer',
        ];
    }

    private function placeOrder(User $user, array $extra = []): Order
    {
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($user)->post('/checkout', $this->payload($extra))->assertRedirect();

        return Order::latest('id')->first();
    }

    private function bank(): array
    {
        return ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_holder' => 'Budi Test'];
    }

    // ---------- Ongkir berdasarkan jarak ----------

    public function test_shipping_cost_depends_on_zone(): void
    {
        $near = CheckoutService::shippingOptions(100000, ['postal_code' => '40111', 'city' => 'Bandung']);
        $jabar = CheckoutService::shippingOptions(100000, ['postal_code' => '16111', 'city' => 'Bogor']);
        $far = CheckoutService::shippingOptions(100000, ['postal_code' => '90111', 'city' => 'Makassar']);
        $unknown = CheckoutService::shippingOptions(100000, null);

        $this->assertSame(10000, $near['reguler']['final_cost']);
        $this->assertSame(20000, $jabar['reguler']['final_cost']);
        $this->assertSame(50000, $far['reguler']['final_cost']);
        $this->assertSame(0, $far['ambil']['final_cost']);
        $this->assertNull($unknown['reguler']['final_cost']);
    }

    public function test_free_shipping_threshold_applies_only_in_configured_zones(): void
    {
        $this->assertSame(0, CheckoutService::shippingOptions(300000, ['postal_code' => '40111'])['reguler']['final_cost']);
        $this->assertSame(50000, CheckoutService::shippingOptions(300000, ['postal_code' => '90111'])['reguler']['final_cost']);
    }

    public function test_postal_code_decides_zone_not_typed_city(): void
    {
        // Mengetik "Bandung" dengan kode pos luar Jawa tidak boleh mendapat tarif dekat.
        $this->assertSame('luar', CheckoutService::zoneFor('90111', 'Bandung')['key']);
        $this->assertSame('dekat', CheckoutService::zoneFor('', 'Cimahi')['key']);
    }

    public function test_checkout_charges_zone_price_server_side(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $order = $this->placeOrder($this->makeUser(), ['city' => 'Makassar', 'postal_code' => '90111']);

        $this->assertSame(50000, $order->shipping);
        $this->assertSame('luar', $order->shipping_zone);
    }

    public function test_shipping_quote_endpoint(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);

        $this->actingAs($user)->getJson('/checkout/ongkir?postal_code=16111&city=Bogor')
            ->assertOk()->assertJsonPath('options.reguler.cost', 20000);
        $this->actingAs($user)->getJson('/checkout/ongkir')
            ->assertOk()->assertJsonPath('options.reguler.cost', null);
    }

    // ---------- Email konfirmasi & kuitansi ----------

    public function test_order_confirmation_email_is_sent_and_receipt_after_payment(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $order = $this->placeOrder($user);

        Mail::assertSent(OrderConfirmationMail::class, fn ($m) => $m->hasTo($user->email));
        Mail::assertNotSent(ReceiptMail::class);

        $this->assertNull(OrderWorkflow::confirmPayment($order));
        Mail::assertSent(ReceiptMail::class, fn ($m) => $m->hasTo($user->email));
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/kuitansi")->assertOk()->assertSee('KWT-');
    }

    public function test_receipt_page_is_blocked_before_payment_and_for_other_users(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $owner = $this->makeUser();
        $order = $this->placeOrder($owner);

        $this->actingAs($owner)->get("/akun/pesanan/{$order->number}/kuitansi")->assertNotFound();
        OrderWorkflow::confirmPayment($order);
        $this->actingAs($this->makeUser())->get("/akun/pesanan/{$order->number}/kuitansi")->assertNotFound();
    }

    public function test_checkout_still_succeeds_when_email_sending_fails(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        $order = $this->placeOrder($this->makeUser());

        $this->assertNotNull($order);
    }

    // ---------- Pembatalan ----------

    public function test_customer_cancels_unpaid_order_and_stock_returns(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $stock = Product::where('slug', 'monstera')->value('stock');
        $order = $this->placeOrder($user);

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/batalkan", ['reason' => 'tidak_jadi'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('Dibatalkan', $order->status);
        $this->assertSame('customer', $order->cancelled_by);
        $this->assertSame($stock, Product::where('slug', 'monstera')->value('stock'));
        $this->assertSame(0, Refund::count());
        Mail::assertSent(NoticeMail::class);
    }

    public function test_cancelling_paid_order_creates_refund_and_requires_bank(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $order = $this->placeOrder($user);
        OrderWorkflow::confirmPayment($order);

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/batalkan", ['reason' => 'tidak_jadi'])
            ->assertSessionHasErrors(['bank_name', 'account_number', 'account_holder']);
        $this->assertSame('Diproses', $order->fresh()->status);

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/batalkan", ['reason' => 'tidak_jadi'] + $this->bank())
            ->assertSessionHasNoErrors();

        $refund = Refund::first();
        $this->assertSame('Diminta', $refund->status);
        $this->assertSame($order->fresh()->total, $refund->amount);
    }

    public function test_shipped_order_cannot_be_cancelled_by_customer(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $order = $this->placeOrder($user);
        OrderWorkflow::confirmPayment($order);
        OrderWorkflow::transition($order, 'Dikirim');

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/batalkan", ['reason' => 'tidak_jadi'] + $this->bank());
        $this->assertSame('Dikirim', $order->fresh()->status);
        $this->assertSame(0, Refund::count());
    }

    public function test_other_user_cannot_cancel_order(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $order = $this->placeOrder($this->makeUser());

        $this->actingAs($this->makeUser())->post("/akun/pesanan/{$order->number}/batalkan", ['reason' => 'tidak_jadi'])->assertNotFound();
    }

    // ---------- Retur & refund ----------

    private function completedPaidOrder(User $user): Order
    {
        $order = $this->placeOrder($user);
        OrderWorkflow::confirmPayment($order);
        OrderWorkflow::transition($order, 'Dikirim');
        OrderWorkflow::transition($order, 'Selesai');

        return $order->fresh();
    }

    public function test_full_return_and_refund_flow_without_sending_goods_back(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $order = $this->completedPaidOrder($user);

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/retur", [
            'reason_code' => 'lainnya', 'description' => 'Tanaman layu setelah tiba, daun menguning.',
        ] + $this->bank())->assertSessionHasNoErrors();

        $ret = OrderReturn::first();
        $this->assertSame('Diajukan', $ret->status);
        // satu pesanan hanya boleh satu retur
        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/retur", [
            'reason_code' => 'lainnya', 'description' => 'Pengajuan kedua tidak boleh.',
        ] + $this->bank());
        $this->assertSame(1, OrderReturn::count());

        $amount = $order->payment->amount - $order->shipping;
        $this->actingAs($admin)->post("/admin/returns/{$ret->number}/putusan", [
            'decision' => 'approve', 'needs_return' => 0, 'refund_amount' => $amount,
        ])->assertSessionHasNoErrors();

        $refund = Refund::first();
        $this->assertSame('Disetujui', $ret->fresh()->status);
        $this->assertSame('Diminta', $refund->status);
        $this->assertSame($amount, $refund->amount);

        $this->actingAs($admin)->post("/admin/refunds/{$refund->number}/selesai", ['transfer_reference' => 'TRX-001'])->assertSessionHasNoErrors();

        $this->assertSame('Selesai', $refund->fresh()->status);
        $this->assertSame('Selesai', $ret->fresh()->status);
        // refund sebagian -> pembayaran belum 'refunded'
        $this->assertSame('paid', $order->fresh()->payment->status);
    }

    public function test_return_with_goods_back_waits_for_shipping_and_receipt(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $order = $this->completedPaidOrder($user);
        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/retur", [
            'reason_code' => 'lainnya', 'description' => 'Barang tidak sesuai pesanan saya.',
        ] + $this->bank());
        $ret = OrderReturn::first();

        $this->actingAs($admin)->post("/admin/returns/{$ret->number}/putusan", [
            'decision' => 'approve', 'needs_return' => 1, 'refund_amount' => $order->payment->amount,
        ]);
        $this->assertSame(0, Refund::count());   // refund baru dibuat setelah barang diterima

        $this->actingAs($user)->post("/akun/retur/{$ret->number}/kirim", ['return_courier' => 'JNE', 'return_tracking' => 'JNE123']);
        $this->assertSame('Dikirim Balik', $ret->fresh()->status);

        $this->actingAs($admin)->post("/admin/returns/{$ret->number}/terima", ['restock' => 1]);
        $this->assertSame('Diterima', $ret->fresh()->status);

        $refund = Refund::first();
        $this->actingAs($admin)->post("/admin/refunds/{$refund->number}/selesai", ['transfer_reference' => 'TRX-9']);
        $this->assertSame('refunded', $order->fresh()->payment->status);   // refund penuh
    }

    public function test_rejected_return_creates_no_refund_and_needs_reason(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $order = $this->completedPaidOrder($user);
        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/retur", [
            'reason_code' => 'lainnya', 'description' => 'Saya berubah pikiran saja.',
        ] + $this->bank());
        $ret = OrderReturn::first();

        $this->actingAs($admin)->post("/admin/returns/{$ret->number}/putusan", ['decision' => 'reject'])->assertSessionHasErrors('admin_note');
        $this->actingAs($admin)->post("/admin/returns/{$ret->number}/putusan", ['decision' => 'reject', 'admin_note' => 'Di luar kebijakan retur.']);

        $this->assertSame('Ditolak', $ret->fresh()->status);
        $this->assertSame(0, Refund::count());
    }

    public function test_return_requires_photo_for_damaged_plants_and_respects_deadline(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $order = $this->completedPaidOrder($user);

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/retur", [
            'reason_code' => 'mati_rusak', 'description' => 'Tanaman mati total saat tiba.',
        ] + $this->bank())->assertSessionHasErrors('evidence');

        $order->shipment->update(['delivered_at' => now()->subDays(30)]);
        $order->update(['updated_at' => now()->subDays(30)]);
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/retur")->assertRedirect("/akun/pesanan/{$order->number}");
    }

    public function test_user_cannot_return_unfinished_or_unpaid_order(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $user = $this->makeUser();
        $order = $this->placeOrder($user);

        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/retur")->assertRedirect();
        $this->assertFalse($order->fresh()->canReturn());
    }

    // ---------- Tanggal blokir & jadwal ulang ----------

    private function service(): Service
    {
        $this->seed(DatabaseSeeder::class);

        return Service::first();
    }

    private function bookingPayload(string $date, string $time = '08.00–10.00'): array
    {
        return ['name' => 'Budi', 'whatsapp' => '081234567890', 'address' => 'Jl. Melati 1, Bandung', 'area' => 20,
            'date' => $date, 'time' => $time];
    }

    public function test_blocked_date_cannot_be_booked_and_availability_reports_it(): void
    {
        Mail::fake();
        $service = $this->service();
        $date = now()->addDays(10)->toDateString();
        BlockedDate::create(['date' => $date, 'service_id' => null, 'reason' => 'Libur Natal']);

        $this->getJson("/jasa/{$service->slug}/ketersediaan?date={$date}")
            ->assertOk()->assertJsonPath('blocked', 'Libur Natal')->assertJsonPath('slots.0.left', 0);

        $this->actingAs($this->makeUser())->post("/jasa/{$service->slug}/pesan", $this->bookingPayload($date))->assertSessionHasErrors('date');
        $this->assertSame(0, Booking::count());
    }

    public function test_block_for_one_service_does_not_affect_others(): void
    {
        $service = $this->service();
        $other = Service::where('id', '!=', $service->id)->first();
        $date = now()->addDays(12)->toDateString();
        BlockedDate::create(['date' => $date, 'service_id' => $service->id, 'reason' => 'Teknisi cuti']);

        $this->assertNotNull($service->blockedReason($date));
        $this->assertNull($other->blockedReason($date));
    }

    public function test_admin_blocking_date_notifies_affected_bookings(): void
    {
        Mail::fake();
        $service = $this->service();
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $date = now()->addDays(8)->toDateString();
        $this->actingAs($user)->post("/jasa/{$service->slug}/pesan", $this->bookingPayload($date))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post('/admin/tanggal-libur', ['start_date' => $date, 'reason' => 'Libur nasional'])->assertSessionHasNoErrors();

        $this->assertSame(1, BlockedDate::count());
        Mail::assertSent(NoticeMail::class, fn ($m) => $m->hasTo($user->email));
        // duplikat tidak membuat baris ganda
        $this->actingAs($admin)->post('/admin/tanggal-libur', ['start_date' => $date]);
        $this->assertSame(1, BlockedDate::count());
    }

    public function test_customer_can_reschedule_booking(): void
    {
        Mail::fake();
        $service = $this->service();
        $user = $this->makeUser();
        $old = now()->addDays(10)->toDateString();
        $new = now()->addDays(14)->toDateString();
        $this->actingAs($user)->post("/jasa/{$service->slug}/pesan", $this->bookingPayload($old));
        $booking = Booking::first();
        $booking->update(['status' => 'Dikonfirmasi']);

        $this->actingAs($user)->post("/pemesanan-jasa/{$booking->code}/jadwal-ulang", ['date' => $new, 'time' => '10.00–12.00'])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame($new, $booking->date->toDateString());
        $this->assertSame('10.00–12.00', $booking->time);
        $this->assertSame($old, $booking->previous_date->toDateString());
        $this->assertSame(1, $booking->reschedule_count);
        $this->assertSame('Menunggu Konfirmasi', $booking->status);
    }

    public function test_reschedule_rejects_blocked_date_full_slot_limit_and_other_users(): void
    {
        Mail::fake();
        $service = $this->service();
        $user = $this->makeUser();
        $old = now()->addDays(10)->toDateString();
        $this->actingAs($user)->post("/jasa/{$service->slug}/pesan", $this->bookingPayload($old));
        $booking = Booking::first();
        $url = "/pemesanan-jasa/{$booking->code}/jadwal-ulang";

        $blocked = now()->addDays(20)->toDateString();
        BlockedDate::create(['date' => $blocked, 'reason' => 'Libur']);
        $this->actingAs($user)->post($url, ['date' => $blocked, 'time' => '10.00–12.00'])->assertSessionHasErrors('date');

        $this->actingAs($this->makeUser())->post($url, ['date' => now()->addDays(15)->toDateString(), 'time' => '10.00–12.00'])->assertNotFound();

        $booking->update(['reschedule_count' => (int) config('clover.booking.max_reschedules')]);
        $this->actingAs($user)->post($url, ['date' => now()->addDays(15)->toDateString(), 'time' => '10.00–12.00'])->assertSessionHasErrors('date');
        $this->assertSame($old, $booking->fresh()->date->toDateString());
    }

    public function test_reschedule_and_cancel_blocked_inside_minimum_notice(): void
    {
        Mail::fake();
        $service = $this->service();
        $user = $this->makeUser();
        $soon = now()->addDay()->toDateString();
        $this->actingAs($user)->post("/jasa/{$service->slug}/pesan", $this->bookingPayload($soon, '08.00–10.00'));
        $booking = Booking::first();
        // paksa jadwal 2 jam lagi -> kurang dari batas minimal
        $booking->update(['date' => now(config('clover.booking.timezone'))->toDateString(), 'time' => now(config('clover.booking.timezone'))->addHours(2)->format('H') . '.00–23.59']);

        $this->actingAs($user)->post("/pemesanan-jasa/{$booking->code}/batal", [])->assertSessionHasErrors('reason');
        $this->assertSame('Menunggu Konfirmasi', $booking->fresh()->status);
    }

    public function test_customer_can_cancel_booking_with_enough_notice(): void
    {
        Mail::fake();
        $service = $this->service();
        $user = $this->makeUser();
        $this->actingAs($user)->post("/jasa/{$service->slug}/pesan", $this->bookingPayload(now()->addDays(10)->toDateString()));
        $booking = Booking::first();

        $this->actingAs($user)->post("/pemesanan-jasa/{$booking->code}/batal", ['reason' => 'Berubah rencana'])->assertSessionHasNoErrors();

        $this->assertSame('Dibatalkan', $booking->fresh()->status);
        Mail::assertSent(NoticeMail::class);
    }
}
