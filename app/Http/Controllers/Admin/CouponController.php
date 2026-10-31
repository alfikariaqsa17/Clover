<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Coupon::class, 'title' => 'Kupon', 'route' => 'admin.coupons',
            'heading' => 'Kupon & Promo', 'search' => ['code', 'description'], 'order' => ['id', 'desc'],
            'columns' => [['Kode', 'code', 'text'], ['Jenis', 'type', 'text'], ['Nilai', 'value', 'text'], ['Terpakai', 'used_count', 'text'], ['Berakhir', 'expires_at', 'date'], ['Aktif', 'is_active', 'bool']],
            'fields' => [
                ['name' => 'code', 'label' => 'Kode Kupon', 'hint' => 'Huruf besar & angka, contoh: WELCOME10'],
                ['name' => 'description', 'label' => 'Deskripsi', 'optional' => true],
                ['name' => 'type', 'label' => 'Jenis Potongan', 'type' => 'select', 'options' => ['percent' => 'Persen (%)', 'fixed' => 'Nominal (Rp)'], 'assoc' => true],
                ['name' => 'value', 'label' => 'Nilai (persen atau rupiah)', 'type' => 'number', 'min' => 1],
                ['name' => 'min_subtotal', 'label' => 'Minimal Belanja (Rp)', 'type' => 'number', 'min' => 0],
                ['name' => 'max_discount', 'label' => 'Maksimal Potongan (Rp, opsional)', 'type' => 'number', 'min' => 0, 'optional' => true],
                ['name' => 'usage_limit', 'label' => 'Batas Pemakaian (opsional)', 'type' => 'number', 'min' => 1, 'optional' => true],
                ['name' => 'starts_at', 'label' => 'Mulai Berlaku', 'type' => 'datetime-local', 'optional' => true],
                ['name' => 'expires_at', 'label' => 'Berakhir', 'type' => 'datetime-local', 'optional' => true],
                ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
            ],
            'rules' => fn (?Model $m) => [
                'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($m?->id)],
                'description' => 'nullable|string|max:200',
                'type' => 'required|in:percent,fixed',
                'value' => 'required|integer|min:1|max:100000000',
                'min_subtotal' => 'required|integer|min:0',
                'max_discount' => 'nullable|integer|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after_or_equal:starts_at',
                'is_active' => 'boolean',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['type' => 'percent', 'min_subtotal' => 0, 'is_active' => true];
    }

    protected function prepare(array $data, ?Model $model, Request $r): array
    {
        $data['code'] = strtoupper($data['code']);
        if ($data['type'] === 'percent' && $data['value'] > 100) {
            $data['value'] = 100;
        }

        return $data;
    }
}
