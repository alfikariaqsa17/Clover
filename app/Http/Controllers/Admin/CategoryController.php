<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Support\Icons;

class CategoryController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Category::class, 'title' => 'Kategori', 'route' => 'admin.categories',
            'heading' => 'Kategori Produk', 'search' => ['name'], 'order' => ['sort_order', 'asc'], 'slug_from' => 'name',
            'image' => ['field' => 'image', 'dir' => 'categories'],
            'columns' => [['Nama', 'name', 'text'], ['Ikon', 'icon', 'text'], ['Urutan', 'sort_order', 'text'], ['Aktif', 'is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Kategori'],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'optional' => true],
                ['name' => 'icon', 'label' => 'Ikon', 'type' => 'select', 'options' => Icons::names()],
                ['name' => 'sort_order', 'label' => 'Urutan tampil', 'type' => 'number', 'min' => 0],
                ['name' => 'is_active', 'label' => 'Tampilkan di situs', 'type' => 'checkbox'],
                ['name' => 'image', 'label' => 'Foto Kategori (opsional)', 'type' => 'file', 'optional' => true],
            ],
            'rules' => fn () => [
                'name' => 'required|string|min:2|max:80',
                'description' => 'nullable|string|max:500',
                'icon' => 'required|string|max:40',
                'sort_order' => 'required|integer|min:0|max:1000',
                'is_active' => 'boolean',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['icon' => 'sprout', 'sort_order' => 0, 'is_active' => true];
    }
}
