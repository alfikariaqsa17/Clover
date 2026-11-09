<?php

namespace Tests\Feature;

use App\Mail\ReceiptMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test pembayaran QRIS GoPay statis: pasang QRIS, checkout, unggah bukti (privat), verifikasi/tolak admin, keamanan.
 * Jalankan: php artisan test --filter=QrisPaymentTest
 */
class QrisPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        Mail::fake();
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

    private function installQris(User $admin, string $name = 'qris.png'): void
    {
        $this->actingAs($admin)->put('/admin/pengaturan-qris', [
            'qris_image' => UploadedFile::fake()->image($name, 500, 500),
            'qris_merchant' => 'Clover Plants', 'qris_enabled' => 1,
        ])->assertSessionHasNoErrors();
    }

    private function qrisOrder(User $user, array $extra = []): Order
    {
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($user)->post('/checkout', $extra + [
            'recipient' => 'Budi Test', 'phone' => '081234567890', 'address' => 'Jl. Melati No. 1', 'city' => 'Bandung',
            'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'qris_gopay',
        ])->assertSessionHasNoErrors();

        return Order::latest('id')->first();
    }

    private function proofFile(string $name = 'bukti.jpg', int $w = 600, int $h = 1200): UploadedFile
    {
        return UploadedFile::fake()->image($name, $w, $h)->size(300);
    }

    private function upload(User $user, Order $order, array $extra = [])
    {
        return $this->actingAs($user)->post("/akun/pesanan/{$order->number}/bayar", $extra + [
            'proof' => $this->proofFile(), 'confirm' => 1,
        ]);
    }

    /** Pesanan QRIS dengan bukti sudah diunggah, siap diperiksa admin. */
    private function awaiting(): array
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $this->installQris($admin);
        $order = $this->qrisOrder($user);
        $this->upload($user, $order)->assertSessionHasNoErrors();

        return [$admin, $user, $order->fresh()];
    }

    // ---------- pengaturan QRIS oleh admin ----------

    public function test_qris_is_not_offered_until_admin_installs_it(): void
    {
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);

        $this->actingAs($user)->get('/checkout')->assertOk()->assertDontSee('QRIS GoPay');
        $this->actingAs($user)->post('/checkout', [
            'recipient' => 'Budi', 'phone' => '081234567890', 'address' => 'Jl. Melati 1', 'city' => 'Bandung',
            'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'qris_gopay',
        ])->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());

        $this->installQris($this->makeUser('admin'));
        $this->actingAs($user)->get('/checkout')->assertOk()->assertSee('QRIS GoPay');
    }

    public function test_admin_replaces_qris_image_and_old_file_is_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $this->installQris($admin, 'a.png');
        $first = Setting::custom('qris_image');
        Storage::disk('public')->assertExists($first);

        $this->installQris($admin, 'b.png');
        $second = Setting::custom('qris_image');

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $this->assertSame('Clover Plants', Setting::custom('qris_merchant'));
    }

    public function test_disabling_qris_hides_it_from_checkout(): void
    {
        $admin = $this->makeUser('admin');
        $this->installQris($admin);
        $this->actingAs($admin)->put('/admin/pengaturan-qris', ['qris_enabled' => 0])->assertSessionHasNoErrors();

        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($user)->get('/checkout')->assertOk()->assertDontSee('QRIS GoPay');
        Storage::disk('public')->assertExists(Setting::custom('qris_image'));   // gambar tidak terhapus
    }

    public function test_qris_settings_reject_bad_files_and_non_admin(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->put('/admin/pengaturan-qris', ['qris_image' => UploadedFile::fake()->create('x.pdf', 50, 'application/pdf')])
            ->assertSessionHasErrors('qris_image');
        $this->actingAs($admin)->put('/admin/pengaturan-qris', ['qris_image' => UploadedFile::fake()->createWithContent('x.png', '<?php echo 1;')])
            ->assertSessionHasErrors('qris_image');
        $this->assertNull(Setting::custom('qris_image'));

        $this->actingAs($this->makeUser())->get('/admin/pengaturan-qris')->assertForbidden();
        $this->actingAs($this->makeUser())->put('/admin/pengaturan-qris', ['qris_image' => UploadedFile::fake()->image('q.png', 500, 500)])->assertForbidden();
    }

    // ---------- checkout & halaman bayar ----------

    public function test_checkout_creates_pending_qris_payment_with_server_computed_total(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $order = $this->qrisOrder($user, ['amount' => 1, 'total' => 1, 'shipping' => 0]);   // nominal dari klien harus diabaikan

        $pay = $order->payment;
        $this->assertSame('qris_gopay', $pay->method);
        $this->assertSame('pending', $pay->status);
        $this->assertSame((int) $order->total, (int) $pay->amount);
        $this->assertGreaterThan(1, (int) $order->total);
        $this->assertSame('Menunggu Pembayaran', $order->status);
    }

    public function test_pay_page_shows_qris_amount_code_and_static_qris_warning(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $order = $this->qrisOrder($user);

        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/bayar")->assertOk()
            ->assertSee($order->number)
            ->assertSee('Rp' . number_format($order->total, 0, ',', '.'))
            ->assertSee('QRIS ini statis')
            ->assertSee('secara manual')
            ->assertSee('Saya Sudah Bayar')
            ->assertSee('storage/qris/', false)
            ->assertSee('Clover Plants');
    }

    public function test_checkout_redirects_qris_orders_to_pay_page(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);

        $res = $this->actingAs($user)->post('/checkout', [
            'recipient' => 'Budi', 'phone' => '081234567890', 'address' => 'Jl. Melati 1', 'city' => 'Bandung',
            'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'qris_gopay',
        ]);
        $res->assertRedirect(route('account.orders.pay', Order::first()));
    }

    public function test_qris_box_is_3_by_4_and_only_crops_poster_shaped_images(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        $this->installQris($admin);   // 500x500: QR persegi -> jangan dipotong
        $order = $this->qrisOrder($user);
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/bayar")->assertOk()->assertSee('--qris-fit: contain', false);

        $this->actingAs($admin)->put('/admin/pengaturan-qris', [
            'qris_image' => UploadedFile::fake()->image('poster.png', 600, 840), 'qris_enabled' => 1,   // 0.71: poster -> isi penuh
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/bayar")->assertOk()->assertSee('--qris-fit: cover', false);
        $this->assertSame('cover', \App\Support\Qris::fit());
    }

    public function test_other_users_cannot_open_pay_page(): void
    {
        $this->installQris($this->makeUser('admin'));
        $order = $this->qrisOrder($this->makeUser());

        $this->actingAs($this->makeUser())->get("/akun/pesanan/{$order->number}/bayar")->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->get("/akun/pesanan/{$order->number}/bayar")->assertRedirect();
    }

    // ---------- unggah bukti ----------

    public function test_uploading_proof_awaits_verification_and_never_marks_paid(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $order = $this->qrisOrder($user);

        $this->upload($user, $order, ['note' => 'a.n. Budi'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('awaiting_verification', $order->payment->status);
        $this->assertNull($order->payment->paid_at);
        $this->assertSame('Menunggu Pembayaran', $order->status);   // belum lunas / belum diproses
        $proof = PaymentProof::first();
        $this->assertSame('submitted', $proof->status);
        $this->assertSame('a.n. Budi', $proof->note);
    }

    public function test_proof_is_stored_privately_not_publicly(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $order = $this->qrisOrder($user);
        $this->upload($user, $order);

        $proof = PaymentProof::first();
        Storage::disk('local')->assertExists($proof->path);
        Storage::disk('public')->assertMissing($proof->path);
        $this->assertStringStartsWith('payment-proofs/' . $order->id . '/', $proof->path);
        $this->assertStringNotContainsString('bukti', $proof->path);   // nama file acak, bukan nama dari pengguna
    }

    public function test_invalid_proof_uploads_are_rejected(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $order = $this->qrisOrder($user);

        $bad = [
            'pdf' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            'php disguised as jpg' => UploadedFile::fake()->createWithContent('bukti.jpg', '<?php system($_GET["c"]);'),
            'svg' => UploadedFile::fake()->createWithContent('bukti.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'too big' => UploadedFile::fake()->image('besar.jpg', 800, 800)->size(5000),
            'too small' => UploadedFile::fake()->image('kecil.jpg', 50, 50),
        ];
        foreach ($bad as $label => $file) {
            $res = $this->upload($user, $order, ['proof' => $file]);
            try {
                $res->assertSessionHasErrors('proof');
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                $this->fail("File '{$label}' seharusnya ditolak, tetapi diterima.");
            }
        }
        $this->upload($user, $order, ['confirm' => 0])->assertSessionHasErrors('confirm');
        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/bayar", ['confirm' => 1])->assertSessionHasErrors('proof');

        $this->assertSame(0, PaymentProof::count());
        $this->assertSame('pending', $order->fresh()->payment->status);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_qr_is_hidden_while_awaiting_verification_to_prevent_double_payment(): void
    {
        [, $user, $order] = $this->awaiting();

        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/bayar")->assertOk()
            ->assertDontSee('storage/qris/', false)
            ->assertDontSee('Saya Sudah Bayar')
            ->assertSee('sedang diverifikasi admin');
    }

    public function test_cannot_upload_again_while_awaiting_verification(): void
    {
        [, $user, $order] = $this->awaiting();

        $this->upload($user, $order);

        $this->assertSame(1, PaymentProof::count());
        $this->assertSame('awaiting_verification', $order->fresh()->payment->status);
    }

    public function test_proof_upload_requires_qris_order_and_open_status(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $order = $this->qrisOrder($user);
        $order->update(['status' => 'Dibatalkan']);

        $this->upload($user, $order);
        $this->assertSame(0, PaymentProof::count());

        $other = $this->makeUser();
        $this->upload($other, $order)->assertNotFound();
    }

    // ---------- akses bukti privat ----------

    public function test_proof_files_are_only_visible_to_owner_and_admin(): void
    {
        [$admin, $user, $order] = $this->awaiting();
        $proof = PaymentProof::first();
        $ownerUrl = "/akun/pesanan/{$order->number}/bayar/bukti/{$proof->id}";
        $adminUrl = "/admin/pembayaran/{$order->number}/bukti/{$proof->id}";

        $this->actingAs($user)->get($ownerUrl)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($admin)->get($adminUrl)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $stranger = $this->makeUser();
        $this->actingAs($stranger)->get($ownerUrl)->assertNotFound();
        $this->actingAs($stranger)->get($adminUrl)->assertForbidden();
        $this->actingAs($user)->get($adminUrl)->assertForbidden();
    }

    public function test_proof_cannot_be_read_through_another_order(): void
    {
        [, $user, $order] = $this->awaiting();
        $proof = PaymentProof::first();

        $other = $this->makeUser();
        $second = $this->qrisOrder($other);
        $this->actingAs($other)->get("/akun/pesanan/{$second->number}/bayar/bukti/{$proof->id}")->assertNotFound();
    }

    public function test_guest_cannot_open_proof(): void
    {
        [, , $order] = $this->awaiting();
        $proof = PaymentProof::first();
        $this->app['auth']->forgetGuards();

        $this->get("/akun/pesanan/{$order->number}/bayar/bukti/{$proof->id}")->assertRedirect();
        $this->get("/admin/pembayaran/{$order->number}/bukti/{$proof->id}")->assertRedirect();
    }

    // ---------- verifikasi admin ----------

    public function test_admin_verifies_payment_then_order_is_paid_and_processed(): void
    {
        [$admin, $user, $order] = $this->awaiting();

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['reference' => 'GP-123'])
            ->assertSessionHasErrors('confirm_funds');
        $this->assertSame('awaiting_verification', $order->fresh()->payment->status);

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1, 'reference' => 'GP-123'])
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('paid', $order->payment->status);
        $this->assertSame('Diproses', $order->status);
        $this->assertSame($admin->id, (int) $order->payment->verified_by);
        $this->assertSame('GP-123', $order->payment->reference);
        $this->assertNotNull($order->payment->paid_at);
        $this->assertSame('verified', PaymentProof::first()->status);
        Mail::assertSent(ReceiptMail::class, fn ($m) => $m->hasTo($user->email));
        $this->assertTrue(UserNotification::where('user_id', $user->id)->where('title', 'like', '%terverifikasi%')->exists());
    }

    public function test_customer_cannot_verify_or_reject_own_payment(): void
    {
        [, $user, $order] = $this->awaiting();

        $this->actingAs($user)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1])->assertForbidden();
        $this->actingAs($user)->post("/admin/pembayaran/{$order->number}/tolak", ['rejection_reason' => 'tes tes'])->assertForbidden();
        $this->actingAs($user)->get("/admin/pembayaran/{$order->number}")->assertForbidden();

        $this->assertSame('awaiting_verification', $order->fresh()->payment->status);
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status);
    }

    public function test_admin_cannot_verify_own_order(): void
    {
        $admin = $this->makeUser('admin');
        $this->installQris($admin);
        $order = $this->qrisOrder($admin);
        $this->upload($admin, $order)->assertSessionHasNoErrors();

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);
        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/tolak", ['rejection_reason' => 'tes tes tes']);

        $this->assertSame('awaiting_verification', $order->fresh()->payment->status);
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status);
        $this->actingAs($admin)->get("/admin/pembayaran/{$order->number}")->assertOk()->assertSee('tidak dapat memverifikasi');
    }

    public function test_cannot_verify_without_proof_or_twice(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $this->installQris($admin);
        $order = $this->qrisOrder($user);   // belum ada bukti

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);
        $this->assertSame('pending', $order->fresh()->payment->status);
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status);

        $this->upload($user, $order);
        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);
        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);

        $this->assertSame('paid', $order->fresh()->payment->status);
        Mail::assertSent(ReceiptMail::class, 1);   // kuitansi hanya sekali
    }

    public function test_verify_is_blocked_when_amount_does_not_match_order_total(): void
    {
        [$admin, , $order] = $this->awaiting();
        $order->payment->update(['amount' => $order->total - 1000]);   // simulasi data tidak konsisten

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);

        $this->assertSame('awaiting_verification', $order->fresh()->payment->status);
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status);
    }

    // ---------- tolak & unggah ulang ----------

    public function test_reject_needs_reason_and_customer_can_reupload_then_be_verified(): void
    {
        [$admin, $user, $order] = $this->awaiting();

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/tolak", [])->assertSessionHasErrors('rejection_reason');
        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/tolak", ['rejection_reason' => 'Nominal di bukti tidak sesuai'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('rejected', $order->payment->status);
        $this->assertSame('Menunggu Pembayaran', $order->status);
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/bayar")->assertOk()->assertSee('Nominal di bukti tidak sesuai')->assertSee('Saya Sudah Bayar');

        $this->upload($user, $order, ['proof' => $this->proofFile('baru.png')])->assertSessionHasNoErrors();
        $this->assertSame('awaiting_verification', $order->fresh()->payment->status);
        $this->assertNull($order->fresh()->payment->rejection_reason);
        $this->assertSame(2, PaymentProof::count());

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);
        $this->assertSame('paid', $order->fresh()->payment->status);
        $this->assertSame(['rejected', 'verified'], PaymentProof::orderBy('id')->pluck('status')->all());
    }

    // ---------- celah alur ----------

    public function test_unpaid_qris_order_cannot_be_pushed_to_processing_by_any_route(): void
    {
        [$admin, , $order] = $this->awaiting();

        $this->actingAs($admin)->patch("/admin/orders/{$order->number}", ['status' => 'Diproses']);
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status);

        $this->actingAs($admin)->post("/admin/orders/{$order->number}/payment");   // jalan pintas lama
        $this->assertSame('Menunggu Pembayaran', $order->fresh()->status);
        $this->assertSame('awaiting_verification', $order->fresh()->payment->status);
    }

    public function test_cancelling_while_awaiting_verification_fails_payment_and_restores_stock(): void
    {
        $this->installQris($this->makeUser('admin'));
        $user = $this->makeUser();
        $stock = Product::where('slug', 'monstera')->value('stock');
        $order = $this->qrisOrder($user);
        $this->upload($user, $order);

        $this->actingAs($user)->post("/akun/pesanan/{$order->number}/batalkan", ['reason' => 'tidak_jadi'])->assertSessionHasNoErrors();

        $this->assertSame('Dibatalkan', $order->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment->status);
        $this->assertSame($stock, Product::where('slug', 'monstera')->value('stock'));

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);
        $this->assertSame('failed', $order->fresh()->payment->status);   // pesanan batal tidak bisa "diverifikasi"
    }

    // ---------- tampilan & admin ----------

    public function test_customer_sees_payment_status_and_history(): void
    {
        [$admin, $user, $order] = $this->awaiting();

        $this->actingAs($user)->get("/akun/pesanan/{$order->number}")->assertOk()->assertSee('Pembayaran QRIS GoPay')->assertSee('Menunggu Verifikasi');
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/bayar")->assertOk()->assertSee('Riwayat pembayaran')->assertSee('Menunggu verifikasi');

        $this->actingAs($admin)->post("/admin/pembayaran/{$order->number}/verifikasi", ['confirm_funds' => 1]);
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}/bayar")->assertOk()->assertSee('diverifikasi admin');
        $this->actingAs($user)->get("/akun/pesanan/{$order->number}")->assertOk()->assertSee('QRIS GoPay');
    }

    public function test_admin_payment_pages_work_and_are_admin_only(): void
    {
        [$admin, $user, $order] = $this->awaiting();

        $this->actingAs($user)->get('/admin/pembayaran')->assertForbidden();
        $this->actingAs($admin)->get('/admin/pembayaran')->assertOk()->assertSee($order->number);
        $this->actingAs($admin)->get("/admin/pembayaran/{$order->number}")->assertOk()
            ->assertSee('Verifikasi')->assertSee('Tolak')->assertSee('Screenshot bukan bukti dana masuk');
        $this->actingAs($admin)->get('/admin/pengaturan-qris')->assertOk();
    }

    public function test_live_feed_reports_awaiting_qris_payments_to_admin(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $this->installQris($admin);
        $order = $this->qrisOrder($user);
        $before = $this->actingAs($admin)->getJson('/admin/live?init=1')->json();
        $this->assertSame(0, $before['badges']['payments']);

        $this->upload($user, $order);

        $after = $this->actingAs($admin)->getJson('/admin/live?init=1')->assertOk();
        $this->assertSame(1, $after->json('badges.payments'));
        $this->assertSame(1, $after->json('counts.proofs'));
        $this->assertNotSame($before['sig'], $after->json('sig'));
    }

    public function test_existing_payment_methods_still_work(): void
    {
        $user = $this->makeUser();
        $p = Product::where('slug', 'monstera')->first();
        $this->actingAs($user)->post('/keranjang', ['product_id' => $p->id, 'qty' => 1]);
        $this->actingAs($user)->post('/checkout', [
            'recipient' => 'Budi', 'phone' => '081234567890', 'address' => 'Jl. Melati 1', 'city' => 'Bandung',
            'postal_code' => '40111', 'shipping_method' => 'reguler', 'payment_method' => 'transfer',
        ])->assertRedirect();

        $order = Order::first();
        $this->assertSame('transfer', $order->payment->method);
        $this->assertNull(\App\Support\OrderWorkflow::confirmPayment($order));   // jalur transfer lama tidak berubah
        $this->assertSame('Diproses', $order->fresh()->status);
    }
}
