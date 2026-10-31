<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PlantController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Plant::class, 'title' => 'Data tanaman', 'route' => 'admin.plants',
            'heading' => 'Katalog Informasi Tanaman', 'search' => ['name', 'latin_name'], 'order' => ['name', 'asc'], 'slug_from' => 'name',
            'image' => ['field' => 'image', 'dir' => 'plants'],
            'columns' => [['Nama', 'name', 'text'], ['Cahaya', 'light_label', 'text'], ['Perawatan', 'care_label', 'text'], ['Siram (hari)', 'water_days', 'text']],
            'fields' => [
                ['name' => 'name', 'label' => 'Nama Umum'],
                ['name' => 'latin_name', 'label' => 'Nama Latin', 'optional' => true],
                ['name' => 'light_level', 'label' => 'Tingkat Cahaya', 'type' => 'select', 'options' => Plant::LIGHT, 'assoc' => true],
                ['name' => 'light_text', 'label' => 'Keterangan Cahaya', 'optional' => true],
                ['name' => 'care_level', 'label' => 'Tingkat Perawatan', 'type' => 'select', 'options' => Plant::CARE, 'assoc' => true],
                ['name' => 'water_days', 'label' => 'Disiram setiap (hari)', 'type' => 'number', 'min' => 1],
                ['name' => 'water_text', 'label' => 'Keterangan Penyiraman', 'optional' => true],
                ['name' => 'humidity', 'label' => 'Kelembapan', 'optional' => true],
                ['name' => 'temperature', 'label' => 'Suhu', 'optional' => true],
                ['name' => 'soil', 'label' => 'Media Tanam', 'optional' => true],
                ['name' => 'fertilizer', 'label' => 'Pemupukan', 'optional' => true],
                ['name' => 'placements', 'label' => 'Penempatan (pisahkan koma)', 'optional' => true, 'hint' => 'Contoh: ruang tamu, kamar, kantor, teras'],
                ['name' => 'pet_safe', 'label' => 'Aman untuk hewan peliharaan', 'type' => 'select', 'options' => ['' => 'Belum diketahui', '1' => 'Aman', '0' => 'Tidak aman'], 'assoc' => true, 'optional' => true],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea', 'optional' => true],
                ['name' => 'tips', 'label' => 'Tips Perawatan', 'type' => 'textarea', 'optional' => true],
                ['name' => 'image', 'label' => 'Foto', 'type' => 'file', 'optional' => true],
            ],
            'rules' => fn () => [
                'name' => 'required|string|min:2|max:120',
                'latin_name' => 'nullable|string|max:120',
                'light_level' => 'required|in:rendah,sedang,tinggi',
                'light_text' => 'nullable|string|max:150',
                'care_level' => 'required|in:mudah,sedang,sulit',
                'water_days' => 'required|integer|min:1|max:90',
                'water_text' => 'nullable|string|max:150',
                'humidity' => 'nullable|string|max:100',
                'temperature' => 'nullable|string|max:100',
                'soil' => 'nullable|string|max:150',
                'fertilizer' => 'nullable|string|max:150',
                'placements' => 'nullable|string|max:150',
                'pet_safe' => 'nullable|in:0,1',
                'description' => 'nullable|string|max:3000',
                'tips' => 'nullable|string|max:3000',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['light_level' => 'sedang', 'care_level' => 'mudah', 'water_days' => 7];
    }

    protected function prepare(array $data, ?Model $model, Request $r): array
    {
        $data['pet_safe'] = ($data['pet_safe'] ?? '') === '' ? null : (bool) $data['pet_safe'];
        $data['placements'] = mb_strtolower((string) ($data['placements'] ?? '')) ?: null;

        return $data;
    }

    protected function values(Model $model): array
    {
        $v = parent::values($model);
        $v['pet_safe'] = $model->pet_safe === null ? '' : ($model->pet_safe ? '1' : '0');

        return $v;
    }
}
