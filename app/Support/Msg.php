<?php

namespace App\Support;

use Illuminate\Http\Request;

class Msg
{
    public const WA_RULE = '/^(\+62|62|0)8[0-9]{7,11}$/';

    public static function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'exists' => ':attribute tidak ditemukan.',
            'email' => 'Format :attribute tidak valid.',
            'regex' => 'Format :attribute tidak valid.',
            'min.string' => ':attribute minimal :min karakter.',
            'max.string' => ':attribute maksimal :max karakter.',
            'min.numeric' => ':attribute minimal :min.',
            'max.numeric' => ':attribute maksimal :max.',
            'between.numeric' => ':attribute harus antara :min dan :max.',
            'min.file' => ':attribute minimal :min KB.',
            'max.file' => 'Ukuran :attribute maksimal :max KB.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'date' => ':attribute bukan tanggal yang valid.',
            'after_or_equal' => ':attribute minimal hari ini.',
            'before_or_equal' => ':attribute tidak boleh melewati hari ini.',
            'in' => ':attribute tidak valid.',
            'unique' => ':attribute sudah terdaftar.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat :values.',
            'uploaded' => 'Unggahan :attribute gagal. Coba file yang lebih kecil.',
            'accepted' => ':attribute harus disetujui.',
            'boolean' => ':attribute tidak valid.',
            'required_without' => ':attribute wajib diisi.',
            'before_or_equal.date' => ':attribute tidak boleh melewati hari ini.',
            'array' => ':attribute tidak valid.',
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => 'Nama', 'whatsapp' => 'Nomor WhatsApp', 'address' => 'Alamat', 'city' => 'Kota',
            'postal_code' => 'Kode pos', 'notes' => 'Catatan', 'service_name' => 'Jenis layanan',
            'date' => 'Tanggal', 'time' => 'Waktu', 'area' => 'Luas taman', 'email' => 'Email',
            'subject' => 'Subjek', 'body' => 'Isi', 'qty' => 'Jumlah', 'product_id' => 'Produk',
            'password' => 'Kata sandi', 'phone' => 'Nomor telepon', 'rating' => 'Rating',
            'current_password' => 'Kata sandi saat ini', 'category_id' => 'Kategori', 'price' => 'Harga',
            'stock' => 'Stok', 'size' => 'Ukuran', 'description' => 'Deskripsi', 'image' => 'Gambar',
            'images' => 'Foto', 'icon' => 'Ikon', 'estimate' => 'Estimasi', 'schedule' => 'Jadwal',
            'includes' => 'Yang termasuk', 'title' => 'Judul', 'excerpt' => 'Ringkasan',
            'published_at' => 'Tanggal terbit', 'status' => 'Status', 'role' => 'Peran', 'label' => 'Label',
            'recipient' => 'Nama penerima', 'shipping_method' => 'Metode pengiriman',
            'payment_method' => 'Metode pembayaran', 'address_id' => 'Alamat', 'code' => 'Kode',
            'type' => 'Jenis', 'value' => 'Nilai', 'consent' => 'Persetujuan privasi', 'slug' => 'Slug',
            'images.*' => 'Foto', 'account_status' => 'Status akun', 'note' => 'Alasan', 'nickname' => 'Nama panggilan', 'text' => 'Teks', 'link_url' => 'Tautan', 'sort_order' => 'Urutan', 'plant_id' => 'Data tanaman', 'article_category_id' => 'Kategori artikel', 'capacity_per_slot' => 'Kuota per jam', 'light_level' => 'Tingkat cahaya', 'care_level' => 'Tingkat perawatan', 'water_days' => 'Interval siram', 'min_subtotal' => 'Minimal belanja', 'usage_limit' => 'Batas pemakaian', 'starts_at' => 'Mulai', 'expires_at' => 'Berakhir', 'ends_at' => 'Berakhir', 'symptoms' => 'Gejala', 'causes' => 'Kemungkinan penyebab', 'suggestions' => 'Saran', 'photo' => 'Foto', 'every_days' => 'Interval hari',
            'next_due_date' => 'Tanggal berikutnya', 'proof' => 'Bukti pembayaran',
            'reason' => 'Alasan', 'reason_code' => 'Alasan', 'reason_note' => 'Keterangan',
            'bank_name' => 'Nama bank', 'account_number' => 'Nomor rekening', 'account_holder' => 'Nama pemilik rekening',
            'evidence' => 'Foto bukti', 'evidence.*' => 'Foto bukti', 'return_courier' => 'Kurir', 'return_tracking' => 'Nomor resi',
            'transfer_reference' => 'Referensi transfer', 'admin_note' => 'Catatan admin', 'refund_amount' => 'Nominal refund',
            'start_date' => 'Tanggal mulai', 'end_date' => 'Tanggal selesai', 'service_id' => 'Layanan',
            'issuer' => 'Penerbit', 'credential_no' => 'Nomor sertifikat', 'verify_url' => 'Tautan verifikasi', 'year' => 'Tahun terbit',
        ];
    }

    /** Aturan validasi rekening tujuan refund. */
    public static function bankRules(): array
    {
        return [
            'bank_name' => 'required|string|min:2|max:60',
            'account_number' => ['required', 'regex:/^[0-9]{6,20}$/'],
            'account_holder' => 'required|string|min:3|max:100',
        ];
    }

    /** Hapus spasi/strip pada isian angka (mis. nomor rekening) sebelum divalidasi. */
    public static function digits(Request $r, string $key): void
    {
        if ($r->filled($key)) {
            $r->merge([$key => preg_replace('/[\s\-]/', '', (string) $r->input($key))]);
        }
    }

    /** Hapus spasi/strip pada nomor WhatsApp sebelum divalidasi. */
    public static function wa(Request $r, string $key = 'whatsapp'): void
    {
        if ($r->filled($key)) {
            $r->merge([$key => preg_replace('/[\s\-]/', '', (string) $r->input($key))]);
        }
    }
}
