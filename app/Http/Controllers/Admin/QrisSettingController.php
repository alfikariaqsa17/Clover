<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Msg;
use App\Support\Qris;
use App\Support\Upload;
use Illuminate\Http\Request;

/** Admin memasang / mengganti gambar QRIS merchant tanpa mengedit kode. */
class QrisSettingController extends Controller
{
    public function edit()
    {
        return view('admin.qris', [
            'imageUrl' => Qris::imageUrl(),
            'fit' => Qris::fit(),
            'ready' => Qris::ready(),
            'enabled' => Qris::enabled(),
            'merchant' => Setting::custom('qris_merchant') ?? '',
        ]);
    }

    public function update(Request $r)
    {
        $d = $r->validate([
            'qris_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:min_width=200,min_height=200,max_width=6000,max_height=6000', 'max:4096'],
            'qris_merchant' => 'nullable|string|max:80',
            'qris_enabled' => 'nullable|boolean',
        ], Msg::messages() + [
            'qris_image.mimes' => 'Format gambar QRIS harus JPG, PNG, atau WebP.',
            'qris_image.mimetypes' => 'Isi file bukan gambar JPG, PNG, atau WebP yang valid.',
            'qris_image.max' => 'Ukuran gambar maksimal 4 MB.',
            'qris_image.dimensions' => 'Dimensi gambar QRIS tidak valid (minimal 200x200 piksel).',
        ], Msg::attributes() + ['qris_image' => 'Gambar QRIS', 'qris_merchant' => 'Nama merchant', 'qris_enabled' => 'Status']);

        if ($r->hasFile('qris_image')) {
            $old = Qris::imagePath();
            Setting::put('qris_image', Upload::save($r->file('qris_image'), 'qris'));
            Upload::delete($old, 'qris');
        }
        Setting::put('qris_merchant', isset($d['qris_merchant']) && trim($d['qris_merchant']) !== '' ? trim($d['qris_merchant']) : null);
        Setting::put('qris_enabled', $r->boolean('qris_enabled') ? 'ya' : 'tidak');

        $msg = 'Pengaturan QRIS disimpan.';
        if (! Qris::ready()) {
            $msg .= Qris::imagePath() ? ' QRIS sedang nonaktif, jadi belum muncul di checkout.' : ' Belum ada gambar QRIS, jadi belum muncul di checkout.';
        }

        return redirect()->route('admin.qris.edit')->with('toast', $msg);
    }
}
