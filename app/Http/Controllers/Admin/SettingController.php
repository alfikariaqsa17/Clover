<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Msg;
use App\Support\Upload;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['texts' => Setting::TEXTS, 'images' => Setting::IMAGES]);
    }

    public function update(Request $r)
    {
        $rules = [];
        $labels = [];
        foreach (Setting::TEXTS as $key => $def) {
            $rules[$key] = 'nullable|string|max:500';
            $labels[$key] = $def[0];
        }
        foreach (Setting::IMAGES as $key => $def) {
            $rules[$key] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120';
            $labels[$key] = $def[0];
        }
        $r->validate($rules, Msg::messages(), $labels + Msg::attributes());

        foreach (array_keys(Setting::TEXTS) as $key) {
            Setting::put($key, trim((string) $r->input($key, '')) ?: null);
        }
        foreach (array_keys(Setting::IMAGES) as $key) {
            $old = Setting::custom($key);
            if ($r->hasFile($key)) {
                Upload::delete($old, 'site');
                Setting::put($key, Upload::save($r->file($key), 'site'));
            } elseif ($r->boolean('remove_' . $key)) {
                Upload::delete($old, 'site');
                Setting::put($key, null);
            }
        }

        return redirect()->route('admin.settings')->with('toast', 'Pengaturan situs tersimpan.');
    }
}
