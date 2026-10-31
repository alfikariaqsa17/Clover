<?php

namespace App\Support;

use App\Models\Wishlist;

class Wish
{
    private static ?array $ids = null;

    public static function has(int $productId): bool
    {
        if (! auth()->check()) {
            return false;
        }
        if (self::$ids === null) {
            self::$ids = Wishlist::where('user_id', auth()->id())->pluck('product_id')->map(fn ($i) => (int) $i)->all();
        }

        return in_array($productId, self::$ids, true);
    }
}
