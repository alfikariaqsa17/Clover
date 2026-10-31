<?php

return [
    /*
     * Metode pengiriman. Biaya Reguler/Ekspres ditentukan ZONA JARAK tujuan (lihat 'shipping_zones').
     * free_over  = gratis ongkir jika subtotal >= nilai ini (null = tidak ada), hanya di zona 'free_zones'.
     * zoned=false = biaya tetap dari 'cost'.
     */
    'shipping' => [
        'reguler' => ['label' => 'Reguler', 'zoned' => true, 'free_over' => 300000, 'free_zones' => ['dekat', 'jabar'], 'estimate' => '2–4 hari kerja', 'courier' => 'Kurir Clover'],
        'ekspres' => ['label' => 'Ekspres', 'zoned' => true, 'free_over' => null, 'estimate' => '1–2 hari kerja', 'courier' => 'Kurir Clover'],
        'ambil' => ['label' => 'Ambil di Toko', 'zoned' => false, 'cost' => 0, 'free_over' => null, 'estimate' => 'Siap diambil 1 hari kerja', 'courier' => 'Ambil sendiri'],
    ],

    /*
     * Zona jarak dari toko (urut dari terdekat). Zona dicocokkan lewat 2 digit awal KODE POS tujuan
     * (kode pos adalah penentu utama), lalu nama kota hanya bila kode pos tidak dikenali.
     * Zona TERAKHIR otomatis dipakai bila tidak ada yang cocok. Ubah angka biaya sesuai tarif kurir Anda.
     */
    'shipping_zones' => [
        'dekat' => [
            'label' => 'Bandung Raya (dekat toko)',
            'postal' => ['40'],
            'cities' => ['bandung', 'cimahi', 'lembang', 'soreang', 'padalarang', 'cileunyi'],
            'costs' => ['reguler' => 10000, 'ekspres' => 20000],
        ],
        'jabar' => [
            'label' => 'Jawa Barat, Banten & Jabodetabek',
            'postal' => ['10', '11', '12', '13', '14', '15', '16', '17', '41', '42', '43', '44', '45', '46'],
            'cities' => ['jakarta', 'bogor', 'depok', 'bekasi', 'tangerang', 'serang', 'cirebon', 'sukabumi', 'garut', 'tasikmalaya', 'karawang', 'purwakarta', 'subang', 'cianjur'],
            'costs' => ['reguler' => 20000, 'ekspres' => 35000],
        ],
        'jawa' => [
            'label' => 'Jawa Tengah, DIY, Jawa Timur & Bali',
            'postal' => ['50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '61', '62', '63', '64', '65', '66', '67', '68', '69', '80', '81', '82'],
            'cities' => ['semarang', 'yogyakarta', 'solo', 'surakarta', 'surabaya', 'malang', 'denpasar', 'bali'],
            'costs' => ['reguler' => 30000, 'ekspres' => 50000],
        ],
        'luar' => [
            'label' => 'Luar Jawa',
            'postal' => [],
            'cities' => [],
            'costs' => ['reguler' => 50000, 'ekspres' => 80000],
        ],
    ],

    /* Pembatalan pesanan oleh pelanggan (hanya sebelum pesanan dikirim). */
    'cancel_reasons' => [
        'salah_pesan' => 'Salah memilih produk / jumlah',
        'ganti_alamat' => 'Ingin mengganti alamat atau metode pengiriman',
        'harga_lain' => 'Menemukan harga yang lebih baik',
        'tidak_jadi' => 'Tidak jadi membeli',
        'lainnya' => 'Alasan lainnya',
    ],

    /* Retur & refund pesanan yang sudah selesai. */
    'return' => [
        'days' => 7,   // batas hari sejak pesanan selesai/diterima
        'reasons' => [
            'mati_rusak' => 'Tanaman mati / rusak saat tiba',
            'salah_item' => 'Barang yang diterima salah',
            'tidak_sesuai' => 'Tidak sesuai deskripsi / foto',
            'kurang' => 'Jumlah barang kurang',
            'lainnya' => 'Alasan lainnya',
        ],
        'evidence_required' => ['mati_rusak', 'salah_item', 'tidak_sesuai', 'kurang'],   // wajib melampirkan foto
    ],

    /* Booking jasa: jadwal ulang & pembatalan. */
    'booking' => [
        'timezone' => 'Asia/Jakarta',   // zona waktu jam kunjungan
        'change_min_hours' => 24,       // jadwal ulang / batal minimal N jam sebelum jam kunjungan
        'max_reschedules' => 2,
        'max_days_ahead' => 90,
    ],

    /* Hanya metode dengan enabled=true yang tampil di checkout. */
    'payments' => [
        'transfer' => [
            'label' => 'Transfer Bank (diverifikasi admin)',
            'enabled' => true,
            'note' => 'Transfer sesuai total, lalu unggah bukti pembayaran. Pesanan diproses setelah admin memverifikasi.',
        ],
        'cod' => [
            'label' => 'Bayar di Tempat (COD)',
            'enabled' => true,
            'note' => 'Bayar tunai saat pesanan diterima.',
        ],
        // QRIS statis GoPay Merchant. Tampil di checkout HANYA bila admin sudah mengunggah gambar QRIS dan opsinya aktif
        // (Admin > Pengaturan QRIS). Tidak ada API/webhook: pembayaran diverifikasi manual oleh admin.
        'qris_gopay' => [
            'label' => 'QRIS GoPay (diverifikasi admin)',
            'enabled' => true,
            'note' => 'QRIS ditampilkan setelah pesanan dibuat. QRIS ini statis: Anda mengetik sendiri nominal yang tepat di aplikasi pembayaran, lalu mengunggah bukti transaksi. Pesanan diproses setelah admin memastikan dana masuk.',
        ],
        // Payment gateway belum terintegrasi. Jangan aktifkan sebelum kode integrasinya dibuat dan diuji.
        'gateway' => [
            'label' => 'Pembayaran Online',
            'enabled' => false,
            'note' => 'Belum tersedia.',
        ],
    ],

    /* Paksa tautan https (isi true di hosting yang sudah memakai SSL). */
    'force_https' => env('CLOVER_FORCE_HTTPS', false),

    /* Alamat balasan email (opsional). Isi MAIL_REPLY_TO_ADDRESS di .env bila ada kotak masuk yang benar-benar dipantau. */
    'mail_reply_to' => env('MAIL_REPLY_TO_ADDRESS'),

    /* Verifikasi email dengan kode OTP */
    'otp' => [
        'digits' => 8,           // jumlah digit kode
        'ttl_minutes' => 10,     // masa berlaku kode
        'max_attempts' => 5,     // percobaan salah maksimal per kode
        'cooldown_seconds' => 60, // jeda minimal antar pengiriman kode
        'max_per_hour' => 5,     // batas penerbitan kode per akun per jam
    ],

    'bank' => [
        'name' => env('CLOVER_BANK_NAME', 'BCA'),
        'number' => env('CLOVER_BANK_NUMBER', '0000000000'),
        'holder' => env('CLOVER_BANK_HOLDER', 'CLOVER'),
    ],

    // Kunci gateway diisi lewat .env (jangan ditulis di kode)
    'gateway' => [
        'server_key' => env('CLOVER_GATEWAY_SERVER_KEY'),
    ],
];
