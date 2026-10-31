<?php

namespace App\Http\Controllers\Admin;

use App\Models\PlantProblem;
use App\Support\Icons;

class PlantProblemController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => PlantProblem::class, 'title' => 'Panduan masalah', 'route' => 'admin.problems',
            'heading' => 'Panduan Kesehatan Tanaman', 'search' => ['title'], 'order' => ['sort_order', 'asc'], 'slug_from' => 'title',
            'columns' => [['Masalah', 'title', 'text'], ['Urutan', 'sort_order', 'text'], ['Aktif', 'is_active', 'bool']],
            'fields' => [
                ['name' => 'title', 'label' => 'Judul Masalah'],
                ['name' => 'icon', 'label' => 'Ikon', 'type' => 'select', 'options' => Icons::names()],
                ['name' => 'symptoms', 'label' => 'Gejala', 'type' => 'textarea'],
                ['name' => 'causes', 'label' => 'Kemungkinan Penyebab', 'type' => 'textarea'],
                ['name' => 'suggestions', 'label' => 'Saran Umum', 'type' => 'textarea'],
                ['name' => 'sort_order', 'label' => 'Urutan', 'type' => 'number', 'min' => 0],
                ['name' => 'is_active', 'label' => 'Tampilkan di situs', 'type' => 'checkbox'],
            ],
            'rules' => fn () => [
                'title' => 'required|string|min:3|max:120',
                'icon' => 'required|string|max:40',
                'symptoms' => 'required|string|min:5|max:2000',
                'causes' => 'required|string|min:5|max:2000',
                'suggestions' => 'required|string|min:5|max:2000',
                'sort_order' => 'required|integer|min:0|max:1000',
                'is_active' => 'boolean',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['icon' => 'leaf', 'sort_order' => 0, 'is_active' => true];
    }
}
