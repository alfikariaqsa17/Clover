<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Illuminate\Validation\Rule;

class BannerController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Banner::class, 'title' => 'Banner', 'route' => 'admin.banners',
            'heading' => 'Banner & Promo', 'search' => ['title'], 'order' => ['type', 'asc'],
            'image' => ['field' => 'image', 'dir' => 'banners'],
            'columns' => [['Foto', 'image_url', 'image'], ['Judul', 'title', 'text'], ['Jenis', 'type', 'text'], ['Urutan', 'sort_order', 'text'], ['Aktif', 'is_active', 'bool']],
            'fields' => [
                ['name' => 'type', 'label' => 'Jenis Banner', 'type' => 'select', 'options' => Banner::TYPES, 'assoc' => true],
                ['name' => 'title', 'label' => 'Judul'],
                ['name' => 'text', 'label' => 'Teks pendukung', 'optional' => true],
                ['name' => 'link_label', 'label' => 'Label Tombol/Tautan', 'optional' => true],
                ['name' => 'link_url', 'label' => 'Tautan (URL atau /halaman)', 'optional' => true, 'hint' => 'Contoh: /tanaman atau https://...'],
                ['name' => 'starts_at', 'label' => 'Tayang Mulai', 'type' => 'datetime-local', 'optional' => true],
                ['name' => 'ends_at', 'label' => 'Tayang Sampai', 'type' => 'datetime-local', 'optional' => true],
                ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
                ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox'],
                ['name' => 'image', 'label' => 'Foto (untuk promo & galeri inspirasi)', 'type' => 'file', 'optional' => true],
            ],
            'rules' => fn () => [
                'type' => ['required', Rule::in(array_keys(Banner::TYPES))],
                'title' => 'required|string|min:3|max:150',
                'text' => 'nullable|string|max:255',
                'link_label' => 'nullable|string|max:60',
                'link_url' => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/)/'],
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after_or_equal:starts_at',
                'sort_order' => 'required|integer|min:0|max:1000',
                'is_active' => 'boolean',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['type' => 'announcement', 'sort_order' => 0, 'is_active' => true];
    }
}
