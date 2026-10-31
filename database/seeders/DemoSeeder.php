<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * OPSIONAL — hanya untuk demo/presentasi: php artisan db:seed --class=DemoSeeder
 * Membuat 1 pesanan dan 1 pemesanan jasa contoh untuk akun user@clover.id.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@clover.id')->first();
        $product = Product::where('slug', 'monstera')->first();
        $service = Service::where('slug', 'perawatan-taman-rumah')->first();
        if (! $user || ! $product || ! $service) {
            return;
        }

        $order = Order::firstOrCreate(['number' => 'CLV-DEMO-0001'], [
            'user_id' => $user->id, 'name' => $user->name, 'whatsapp' => '081298765432',
            'address' => 'Jl. Kenanga No.5', 'city' => 'Bandung', 'postal_code' => '40111',
            'subtotal' => $product->price, 'discount' => 0, 'shipping' => 20000, 'shipping_method' => 'reguler',
            'total' => $product->price + 20000, 'status' => 'Diproses',
        ]);
        if ($order->wasRecentlyCreated) {
            $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'price' => $product->price, 'qty' => 1]);
            Payment::create(['order_id' => $order->id, 'method' => 'cod', 'amount' => $order->total, 'status' => 'pending']);
            Shipment::create(['order_id' => $order->id, 'courier' => 'Kurir Clover', 'service' => 'Reguler', 'cost' => 20000, 'status' => 'pending']);
        }

        Booking::firstOrCreate(['code' => 'JSA-DEMO-0001'], [
            'user_id' => $user->id, 'service_id' => $service->id, 'service_name' => $service->name,
            'name' => $user->name, 'whatsapp' => '081298765432', 'address' => 'Jl. Kenanga No.5, Bandung',
            'date' => now()->addDays(3)->toDateString(), 'time' => '08.00–10.00', 'area' => 30,
            'status' => 'Menunggu Konfirmasi',
        ]);
    }
}
