<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareCalendarController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PlantGuideController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserPlantController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\EnsureAccountApproved;
use Illuminate\Support\Facades\Route;

/* ===================== PUBLIK ===================== */
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
Route::post('/kontak', [PageController::class, 'contactStore'])->middleware('throttle:10,1')->name('contact.store');
Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('privacy');
Route::get('/syarat-layanan', [PageController::class, 'terms'])->name('terms');
Route::post('/newsletter', [PageController::class, 'newsletter'])->middleware('throttle:10,1')->name('newsletter');
Route::get('/lacak-pesanan', [PageController::class, 'track'])->name('track');

Route::get('/tanaman', [ProductController::class, 'index'])->name('products');
Route::get('/cari/saran', [ProductController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/tanaman/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/panduan-perawatan', [PlantGuideController::class, 'index'])->name('guide');
Route::get('/panduan-perawatan/{plant}', [PlantGuideController::class, 'show'])->name('guide.show');
Route::get('/plant-finder', [PlantGuideController::class, 'finder'])->name('finder');
Route::get('/kesehatan-tanaman', [PlantGuideController::class, 'health'])->name('health');

Route::get('/jasa', [ServiceController::class, 'index'])->name('services');
Route::get('/jasa/{service}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/jasa/{service}/ketersediaan', [ServiceController::class, 'availability'])->name('services.availability');

Route::get('/tips', [ArticleController::class, 'index'])->name('tutorial');
Route::get('/tips/{article}', [ArticleController::class, 'show'])->name('tutorial.show');

Route::get('/keranjang', [CartController::class, 'page'])->name('cart.page');
Route::post('/keranjang', [CartController::class, 'add'])->name('cart.add');
Route::patch('/keranjang/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/keranjang/{product}', [CartController::class, 'remove'])->name('cart.remove');

/* ===================== AUTENTIKASI ===================== */
Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.attempt');
Route::get('/verifikasi-email', [EmailVerificationController::class, 'show'])->name('verification.notice');
Route::post('/verifikasi-email', [EmailVerificationController::class, 'verify'])->middleware('throttle:otp-verify')->name('verification.verify');
Route::post('/verifikasi-email/kirim-ulang', [EmailVerificationController::class, 'resend'])->middleware('throttle:otp-resend')->name('verification.resend');
Route::post('/verifikasi-email/batal', [EmailVerificationController::class, 'cancel'])->name('verification.cancel');
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

Route::get('/lupa-password', [PasswordResetController::class, 'request'])->name('password.request');
Route::post('/lupa-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'form'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');

/* ===================== PELANGGAN (login + akun disetujui admin) ===================== */
Route::middleware(['auth', EnsureAccountApproved::class])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::get('/checkout/ongkir', [CheckoutController::class, 'shippingQuote'])->name('checkout.shipping');
    Route::post('/tanaman/{product}/tanya', [QuestionController::class, 'store'])->name('questions.store');
    Route::post('/checkout/kupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon');
    Route::delete('/checkout/kupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.coupon.remove');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::post('/jasa/{service}/pesan', [ServiceController::class, 'book'])->name('services.book');
    Route::get('/pemesanan-jasa/{booking}', [ServiceController::class, 'done'])->name('booking.done');
    Route::post('/pemesanan-jasa/{booking}/jadwal-ulang', [ServiceController::class, 'reschedule'])->name('booking.reschedule');
    Route::post('/pemesanan-jasa/{booking}/batal', [ServiceController::class, 'cancel'])->name('booking.cancel');

    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/tanaman/{product}/ulasan', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/akun', [AccountController::class, 'index'])->name('account');
    Route::get('/akun/live', LiveController::class)->middleware('throttle:240,1')->name('account.live');
    Route::get('/akun/pesanan', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/akun/pesanan/{order}', [AccountController::class, 'orderShow'])->name('account.orders.show');
    Route::post('/akun/pesanan/{order}/bukti', [AccountController::class, 'uploadProof'])->name('account.orders.proof');
    Route::get('/akun/pesanan/{order}/bayar', [PaymentController::class, 'show'])->name('account.orders.pay');
    Route::post('/akun/pesanan/{order}/bayar', [PaymentController::class, 'submit'])->middleware('throttle:10,1')->name('account.orders.pay.submit');
    Route::get('/akun/pesanan/{order}/bayar/bukti/{proof}', [PaymentController::class, 'file'])->name('account.orders.pay.proof');
    Route::get('/akun/pesanan/{order}/kuitansi', [AccountController::class, 'receipt'])->name('account.orders.receipt');
    Route::post('/akun/pesanan/{order}/batalkan', [AccountController::class, 'cancelOrder'])->name('account.orders.cancel');
    Route::get('/akun/pesanan/{order}/retur', [ReturnController::class, 'create'])->name('account.returns.create');
    Route::post('/akun/pesanan/{order}/retur', [ReturnController::class, 'store'])->name('account.returns.store');
    Route::get('/akun/retur', [ReturnController::class, 'index'])->name('account.returns');
    Route::get('/akun/retur/{orderReturn}', [ReturnController::class, 'show'])->name('account.returns.show');
    Route::post('/akun/retur/{orderReturn}/kirim', [ReturnController::class, 'ship'])->name('account.returns.ship');
    Route::post('/akun/refund/{refund}/rekening', [AccountController::class, 'refundBank'])->name('account.refunds.bank');
    Route::get('/akun/jasa', [AccountController::class, 'bookings'])->name('account.bookings');
    Route::get('/akun/wishlist', [AccountController::class, 'wishlist'])->name('account.wishlist');
    Route::get('/akun/profil', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/akun/profil', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/akun/password', [AccountController::class, 'updatePassword'])->name('account.password');

    Route::get('/akun/alamat', [AddressController::class, 'index'])->name('account.addresses');
    Route::post('/akun/alamat', [AddressController::class, 'store'])->name('account.addresses.store');
    Route::put('/akun/alamat/{address}', [AddressController::class, 'update'])->name('account.addresses.update');
    Route::delete('/akun/alamat/{address}', [AddressController::class, 'destroy'])->name('account.addresses.destroy');

    Route::get('/akun/tanaman-saya', [UserPlantController::class, 'index'])->name('account.plants');
    Route::post('/akun/tanaman-saya', [UserPlantController::class, 'store'])->name('account.plants.store');
    Route::get('/akun/tanaman-saya/{userPlant}', [UserPlantController::class, 'show'])->name('account.plants.show');
    Route::put('/akun/tanaman-saya/{userPlant}', [UserPlantController::class, 'update'])->name('account.plants.update');
    Route::delete('/akun/tanaman-saya/{userPlant}', [UserPlantController::class, 'destroy'])->name('account.plants.destroy');
    Route::post('/akun/tanaman-saya/{userPlant}/jadwal', [UserPlantController::class, 'scheduleStore'])->name('account.plants.schedules.store');
    Route::delete('/akun/jadwal/{schedule}', [UserPlantController::class, 'scheduleDestroy'])->name('account.schedules.destroy');
    Route::post('/akun/jadwal/{schedule}/selesai', [UserPlantController::class, 'scheduleDone'])->name('account.schedules.done');
    Route::post('/akun/tanaman-saya/{userPlant}/catatan', [UserPlantController::class, 'logStore'])->name('account.plants.logs.store');
    Route::get('/akun/kalender', [CareCalendarController::class, 'index'])->name('account.calendar');

    Route::get('/akun/notifikasi', [NotificationController::class, 'index'])->name('account.notifications');
    Route::post('/akun/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('account.notifications.readall');
    Route::post('/akun/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('account.notifications.read');
});

Route::get('/pesanan/{order}', [CheckoutController::class, 'done'])->middleware(['auth', EnsureAccountApproved::class])->name('order.done');

/* ===================== ADMIN (login + role admin) ===================== */
Route::middleware(['auth', AdminOnly::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('laporan', [Admin\ReportController::class, 'index'])->name('reports');
    Route::get('laporan/ekspor', [Admin\ReportController::class, 'export'])->name('reports.export');

    Route::post('products/{product}/images', [Admin\ProductController::class, 'imageStore'])->name('products.images.store');
    Route::patch('product-images/{image}/primary', [Admin\ProductController::class, 'imagePrimary'])->name('products.images.primary');
    Route::delete('product-images/{image}', [Admin\ProductController::class, 'imageDestroy'])->name('products.images.destroy');

    Route::resource('products', Admin\ProductController::class)->except(['show']);
    Route::resource('categories', Admin\CategoryController::class)->except(['show']);
    Route::resource('plants', Admin\PlantController::class)->except(['show']);
    Route::resource('problems', Admin\PlantProblemController::class)->except(['show']);
    Route::resource('services', Admin\ServiceController::class)->except(['show']);
    Route::resource('article-categories', Admin\ArticleCategoryController::class)->except(['show']);
    Route::resource('articles', Admin\ArticleController::class)->except(['show']);
    Route::resource('coupons', Admin\CouponController::class)->except(['show']);
    Route::resource('banners', Admin\BannerController::class)->except(['show']);

    Route::get('orders/export', [Admin\OrderController::class, 'export'])->name('orders.export');
    Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{order}/kuitansi', [Admin\OrderController::class, 'receipt'])->name('orders.receipt');

    Route::get('pembayaran', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('pembayaran/{order}', [Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::post('pembayaran/{order}/verifikasi', [Admin\PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('pembayaran/{order}/tolak', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('pembayaran/{order}/bukti/{proof}', [Admin\PaymentController::class, 'file'])->name('payments.proof');
    Route::patch('orders/{order}', [Admin\OrderController::class, 'status'])->name('orders.status');
    Route::post('orders/{order}/payment', [Admin\OrderController::class, 'confirmPayment'])->name('orders.payment');
    Route::put('orders/{order}/shipment', [Admin\OrderController::class, 'shipment'])->name('orders.shipment');

    Route::get('bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
    Route::patch('bookings/{booking}', [Admin\BookingController::class, 'status'])->name('bookings.status');
    Route::delete('bookings/{booking}', [Admin\BookingController::class, 'destroy'])->name('bookings.destroy');

    Route::get('tanggal-libur', [Admin\BlockedDateController::class, 'index'])->name('blocked-dates.index');
    Route::post('tanggal-libur', [Admin\BlockedDateController::class, 'store'])->name('blocked-dates.store');
    Route::delete('tanggal-libur/{blockedDate}', [Admin\BlockedDateController::class, 'destroy'])->name('blocked-dates.destroy');

    Route::get('returns', [Admin\ReturnController::class, 'index'])->name('returns.index');
    Route::get('returns/{orderReturn}', [Admin\ReturnController::class, 'show'])->name('returns.show');
    Route::post('returns/{orderReturn}/putusan', [Admin\ReturnController::class, 'decide'])->name('returns.decide');
    Route::post('returns/{orderReturn}/terima', [Admin\ReturnController::class, 'receive'])->name('returns.receive');

    Route::get('refunds', [Admin\RefundController::class, 'index'])->name('refunds.index');
    Route::get('refunds/{refund}', [Admin\RefundController::class, 'show'])->name('refunds.show');
    Route::post('refunds/{refund}/selesai', [Admin\RefundController::class, 'complete'])->name('refunds.complete');
    Route::post('refunds/{refund}/tolak', [Admin\RefundController::class, 'reject'])->name('refunds.reject');

    Route::get('live', Admin\LiveController::class)->middleware('throttle:240,1')->name('live');

    Route::get('tanya-jawab', [Admin\QuestionController::class, 'index'])->name('questions.index');
    Route::post('tanya-jawab/{question}/jawab', [Admin\QuestionController::class, 'answer'])->name('questions.answer');
    Route::post('tanya-jawab/{question}/tampil', [Admin\QuestionController::class, 'toggle'])->name('questions.toggle');
    Route::delete('tanya-jawab/{question}', [Admin\QuestionController::class, 'destroy'])->name('questions.destroy');

    Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}', [Admin\ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('reviews/{review}', [Admin\ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('messages', [Admin\MessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
    Route::delete('messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('subscribers/export', [Admin\SubscriberController::class, 'export'])->name('subscribers.export');
    Route::get('subscribers', [Admin\SubscriberController::class, 'index'])->name('subscribers.index');
    Route::delete('subscribers/{subscriber}', [Admin\SubscriberController::class, 'destroy'])->name('subscribers.destroy');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::post('users/{user}/setujui', [Admin\UserController::class, 'approve'])->name('users.approve');
    Route::post('users/{user}/tolak', [Admin\UserController::class, 'reject'])->name('users.reject');
    Route::delete('users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::get('pengaturan', [Admin\SettingController::class, 'edit'])->name('settings');
    Route::put('pengaturan', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::get('pengaturan-qris', [Admin\QrisSettingController::class, 'edit'])->name('qris.edit');
    Route::put('pengaturan-qris', [Admin\QrisSettingController::class, 'update'])->name('qris.update');
});
