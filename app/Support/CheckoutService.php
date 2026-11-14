<?php

namespace App\Support;

use App\Models\Coupon;

/** Semua perhitungan harga dilakukan di server dari data database. */
class CheckoutService
{
    /**
     * Zona jarak tujuan. Kode pos (2 digit awal) adalah penentu utama; nama kota hanya dipakai bila kode pos
     * tidak dikenali. Bila tidak ada yang cocok, dipakai zona terakhir (terjauh) agar tidak merugikan toko.
     *
     * @return array{key:string,label:string}
     */
    public static function zoneFor(?string $postal, ?string $city): array
    {
        $zones = config('clover.shipping_zones');
        $postal = preg_replace('/\D/', '', (string) $postal);

        if (strlen($postal) >= 2) {
            $p2 = substr($postal, 0, 2);
            foreach ($zones as $key => $z) {
                if (in_array($p2, $z['postal'] ?? [], true)) {
                    return ['key' => $key, 'label' => $z['label']];
                }
            }
        }

        $c = mb_strtolower(trim((string) $city));
        if ($c !== '') {
            foreach ($zones as $key => $z) {
                foreach ($z['cities'] ?? [] as $name) {
                    if (str_contains($c, $name)) {
                        return ['key' => $key, 'label' => $z['label']];
                    }
                }
            }
        }

        $last = array_key_last($zones);

        return ['key' => $last, 'label' => $zones[$last]['label']];
    }

    /**
     * Opsi pengiriman beserta biaya final.
     * $dest = ['postal_code' => ..., 'city' => ...]; bila null (alamat belum diketahui), biaya metode
     * berzona = null ("dihitung setelah alamat diisi").
     */
    public static function shippingOptions(int $subtotal, ?array $dest = null): array
    {
        $zone = $dest ? self::zoneFor($dest['postal_code'] ?? null, $dest['city'] ?? null) : null;
        $out = [];

        foreach (config('clover.shipping') as $key => $m) {
            $zoned = ! empty($m['zoned']);
            $hasFree = isset($m['free_over']) && $m['free_over'] !== null && $subtotal >= $m['free_over'];

            if ($zoned) {
                if ($zone === null) {
                    $final = null;
                } else {
                    $freeHere = $hasFree && in_array($zone['key'], $m['free_zones'] ?? array_keys(config('clover.shipping_zones')), true);
                    $final = $freeHere ? 0 : (int) config('clover.shipping_zones.' . $zone['key'] . '.costs.' . $key, 0);
                }
            } else {
                $final = $hasFree ? 0 : (int) ($m['cost'] ?? 0);
            }

            $out[$key] = $m + [
                'key' => $key,
                'final_cost' => $final,
                'zone' => $zoned && $zone ? $zone['key'] : null,
                'zone_label' => $zoned && $zone ? $zone['label'] : null,
            ];
        }

        return $out;
    }

    /** Biaya terendah suatu metode lintas zona (untuk teks "mulai dari" di halaman produk). */
    public static function minCost(string $method): ?int
    {
        $m = config('clover.shipping.' . $method);
        if (! $m) {
            return null;
        }
        if (empty($m['zoned'])) {
            return (int) ($m['cost'] ?? 0);
        }
        $costs = array_map(fn ($z) => (int) ($z['costs'][$method] ?? 0), config('clover.shipping_zones'));

        return $costs ? min($costs) : null;
    }

    public static function paymentOptions(): array
    {
        $out = [];
        foreach (config('clover.payments') as $key => $m) {
            if (! empty($m['enabled'])) {
                if ($key === Qris::METHOD && ! Qris::ready()) {
                    continue;   // QRIS belum dipasang admin: jangan tawarkan ke pelanggan
                }
                $out[$key] = $m + ['key' => $key];
            }
        }

        return $out;
    }

    /**
     * @return array{subtotal:int, discount:int, shipping:?int, total:?int, coupon:?Coupon, coupon_error:?string}
     */
    public static function quote(array $lines, ?string $couponCode, string $shippingKey, ?array $dest = null, ?int $userId = null): array
    {
        $subtotal = (int) array_sum(array_column($lines, 'subtotal'));
        $options = self::shippingOptions($subtotal, $dest);
        $shipping = $options[$shippingKey]['final_cost'] ?? 0;

        $coupon = null;
        $error = null;
        $discount = 0;
        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->first();
            if (! $coupon) {
                $error = 'Kode kupon tidak ditemukan.';
            } elseif (($error = $coupon->problem($subtotal, $userId)) !== null) {
                $coupon = null;
            } else {
                $discount = $coupon->discountFor($subtotal);
            }
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping === null ? null : (int) $shipping,
            'total' => $shipping === null ? null : max(0, $subtotal - $discount + (int) $shipping),
            'coupon' => $coupon,
            'coupon_error' => $error,
        ];
    }
}
