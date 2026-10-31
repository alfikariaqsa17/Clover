<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ServiceController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Service::class, 'title' => 'Layanan', 'route' => 'admin.services',
            'heading' => 'Layanan Jasa Perawatan', 'search' => ['name'], 'order' => ['id', 'asc'], 'slug_from' => 'name',
            'image' => ['field' => 'image', 'dir' => 'services'],
            'columns' => [['Foto', 'image_url', 'image'], ['Nama', 'name', 'text'], ['Harga mulai', 'price', 'money'], ['Kuota/jam', 'capacity_per_slot', 'text'], ['Aktif', 'is_active', 'bool']],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Layanan'],
                ['name' => 'icon', 'label' => 'Ikon', 'type' => 'select', 'options' => \App\Support\Icons::names()],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ['name' => 'price', 'label' => 'Harga Mulai (Rp)', 'type' => 'number', 'min' => 0],
                ['name' => 'estimate', 'label' => 'Estimasi Waktu'],
                ['name' => 'schedule', 'label' => 'Jadwal Kunjungan'],
                ['name' => 'includes', 'label' => 'Yang Termasuk (satu per baris)', 'type' => 'textarea'],
                ['name' => 'capacity_per_slot', 'label' => 'Kuota Pemesanan per Jam', 'type' => 'number', 'min' => 1, 'hint' => 'Jumlah tim yang tersedia pada jam yang sama.'],
                ['name' => 'is_active', 'label' => 'Tampilkan & terima pemesanan', 'type' => 'checkbox'],
                ['name' => 'image', 'label' => 'Foto Layanan', 'type' => 'file', 'optional' => true],
            ],
            'rules' => fn () => [
                'name' => 'required|string|min:3|max:120',
                'icon' => 'required|string|max:40',
                'description' => 'required|string|min:5|max:1000',
                'price' => 'required|integer|min:0|max:1000000000',
                'estimate' => 'required|string|max:100',
                'schedule' => 'required|string|max:150',
                'includes' => 'required|string|max:2000',
                'capacity_per_slot' => 'required|integer|min:1|max:50',
                'is_active' => 'boolean',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['icon' => 'leaf', 'capacity_per_slot' => 2, 'is_active' => true];
    }

    protected function prepare(array $data, ?Model $model, Request $r): array
    {
        $data['includes'] = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['includes']))));

        return $data;
    }

    protected function values(Model $model): array
    {
        $v = parent::values($model);
        $v['includes'] = implode("\n", $model->includes ?? []);

        return $v;
    }
}
