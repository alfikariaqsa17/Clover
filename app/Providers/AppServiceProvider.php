<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Order;
use App\Models\Review;
use App\Models\Category;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Cart;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Pembatas permintaan OTP (per akun yang sedang diverifikasi + per IP)
        RateLimiter::for('otp-verify', fn (Request $r) => Limit::perMinute(10)->by(($r->session()->get('otp_user_id') ?? 'guest') . '|' . $r->ip()));
        RateLimiter::for('otp-resend', fn (Request $r) => Limit::perMinute(3)->by(($r->session()->get('otp_user_id') ?? 'guest') . '|' . $r->ip()));

        if (config('clover.force_https')) {
            URL::forceScheme('https');
        }

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);
            $minutes = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

            return (new MailMessage)
                ->subject('Reset kata sandi akun Clover')
                ->greeting('Halo, ' . $notifiable->name . '!')
                ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.')
                ->action('Buat Kata Sandi Baru', $url)
                ->line('Tautan berlaku ' . $minutes . ' menit. Jika Anda tidak meminta reset, abaikan email ini.')
                ->salutation('Salam hijau, Tim Clover');
        });

        Paginator::defaultView('vendor.pagination.clover');
        Paginator::defaultSimpleView('vendor.pagination.clover');

        View::composer('layouts.app', function ($view) {
            $lines = Cart::lines();
            $view->with([
                'cartLines' => $lines,
                'cartCount' => array_sum(array_column($lines, 'qty')),
                'cartTotal' => array_sum(array_column($lines, 'subtotal')),
                'announcement' => Banner::live('announcement')->first(),
                'navCategories' => Category::active()->take(8)->get(),
                'unreadCount' => auth()->check()
                    ? UserNotification::where('user_id', auth()->id())->whereNull('read_at')->count()
                    : 0,
            ]);
        });

        View::composer('layouts.admin', function ($view) {
            $view->with('badges', [
                'orders' => Order::where('status', 'Menunggu Pembayaran')->count(),
                'bookings' => Booking::where('status', 'Menunggu Konfirmasi')->count(),
                'questions' => \App\Models\ProductQuestion::whereNull('answer')->count(),
                'payments' => \App\Models\Payment::where('status', 'awaiting_verification')->count(),
                'returns' => \App\Models\OrderReturn::whereIn('status', ['Diajukan', 'Dikirim Balik'])->count(),
                'refunds' => \App\Models\Refund::where('status', 'Diminta')->count(),
                'messages' => Message::where('is_read', false)->count(),
                'reviews' => Review::where('status', 'pending')->count(),
                'accounts' => User::where('account_status', 'pending')->count(),
            ]);
        });

        // View akun memakai jumlah notifikasi belum dibaca (badge di menu samping).
        View::composer('account.*', function ($view) {
            $view->with('unreadCount', auth()->check()
                ? UserNotification::where('user_id', auth()->id())->whereNull('read_at')->count()
                : 0);
        });
    }
}
