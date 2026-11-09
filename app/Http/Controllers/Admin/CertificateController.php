<?php

namespace App\Http\Controllers\Admin;

use App\Models\Certificate;

/** Admin: unggah, urutkan, sembunyikan, dan hapus foto sertifikat tanpa mengedit kode. */
class CertificateController extends CrudController
{
    protected function cfg(): array
    {
        return [
            'model' => Certificate::class, 'title' => 'Foto Sertifikat', 'route' => 'admin.certificates',
            'heading' => 'Sertifikat & Legalitas', 'search' => [], 'order' => ['sort_order', 'asc'],
            'image' => ['field' => 'image', 'dir' => 'certificates'],
            'columns' => [['Foto', 'image_url', 'image'], ['Urutan', 'sort_order', 'text'], ['Tampil', 'is_active', 'bool']],
            'fields' => [
                ['name' => 'image', 'label' => 'Foto sertifikat (JPG, PNG, WebP, maks 4 MB)', 'type' => 'file', 'hint' => 'Unggah hanya dokumen ASLI milik usaha Anda. Tutup (sensor) data pribadi seperti NIK, alamat rumah, atau nomor rekening sebelum diunggah. Foto yang tegak lurus dan terang lebih meyakinkan.'],
                ['name' => 'sort_order', 'label' => 'Urutan tampil (kecil = paling depan)', 'type' => 'number', 'min' => 0],
                ['name' => 'is_active', 'label' => 'Tampilkan di website', 'type' => 'checkbox'],
            ],
            'rules' => fn ($m) => [
                'sort_order' => 'required|integer|min:0|max:1000',
                'is_active' => 'boolean',
                // wajib saat membuat baru; saat mengganti boleh dikosongkan (foto lama tetap dipakai)
                'image' => [($m && $m->exists) ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            ],
        ];
    }

    protected function defaults(): array
    {
        return ['sort_order' => 0, 'is_active' => true];
    }
}
