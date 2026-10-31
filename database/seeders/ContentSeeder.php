<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Banner;
use App\Models\Coupon;
use App\Models\PlantProblem;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->services();
        $this->articles();
        $this->problems();
        $this->banners();
        $this->coupons();
    }

    private function services(): void
    {
        $rows = [
            ['perawatan-taman-rumah', 'Perawatan Taman Rumah', 'home', 'Perawatan rutin untuk menjaga taman rumah tetap bersih, sehat, dan indah.', 250000, '3–4 jam / kunjungan', 'Mingguan, 2 mingguan, atau bulanan', ['Pemangkasan tanaman', 'Pembersihan taman', 'Penyiraman', 'Pengecekan kondisi tanaman', 'Perawatan dasar']],
            ['perawatan-tanaman-indoor', 'Perawatan Tanaman Kantor & Indoor', 'sprout', 'Perawatan tanaman dalam ruangan atau kantor agar tetap segar dan tumbuh optimal.', 150000, '1–2 jam / kunjungan', 'Mingguan atau bulanan', ['Pengecekan kesehatan daun dan akar', 'Pembersihan daun', 'Pemupukan', 'Penggantian media tanam bila perlu', 'Saran penempatan cahaya']],
            ['repotting-tanaman', 'Repotting Tanaman', 'pot', 'Pemindahan tanaman ke pot dan media baru agar akar tumbuh leluasa.', 120000, '1–2 jam', 'Sekali kunjungan', ['Pemilihan ukuran pot', 'Penggantian media tanam', 'Pemeriksaan akar', 'Penyiraman awal', 'Pembersihan area kerja']],
            ['penataan-desain-taman', 'Penataan Tanaman & Desain Taman', 'sparkle', 'Membantu menata tanaman dan taman agar indah, rapi, dan sesuai kebutuhan ruang.', 1500000, '3–7 hari kerja', 'Sesuai kesepakatan setelah survei', ['Survei dan konsultasi konsep', 'Rancangan tata letak', 'Pemilihan tanaman', 'Penanaman dan penataan', 'Finishing area']],
            ['konsultasi-perawatan', 'Konsultasi Perawatan', 'message', 'Konsultasi mengenai kondisi tanaman dan kebutuhan perawatannya.', 100000, '60 menit', 'Kunjungan atau video call', ['Analisis kondisi tanaman', 'Rekomendasi perawatan', 'Rencana pemupukan dan penyiraman', 'Tanya jawab langsung']],
            ['pembersihan-taman', 'Pembersihan Taman', 'scissors', 'Membersihkan area taman dari daun kering, gulma, dan kotoran.', 200000, '2–3 jam', 'Sekali kunjungan atau berkala', ['Pembersihan daun kering', 'Pencabutan gulma', 'Pembuangan sampah taman', 'Perapian pinggiran taman']],
        ];
        foreach ($rows as [$slug, $name, $icon, $desc, $price, $est, $sched, $inc]) {
            $s = Service::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'icon' => $icon, 'description' => $desc, 'price' => $price,
                'estimate' => $est, 'schedule' => $sched, 'includes' => $inc,
                'capacity_per_slot' => 2, 'is_active' => true,
            ]);
            // Ikon versi lama berupa emoji -> diganti nama ikon SVG
            if (! preg_match('/^[a-z-]+$/', (string) $s->icon)) {
                $s->update(['icon' => $icon]);
            }
        }
    }

    private function articles(): void
    {
        $cats = [];
        foreach ([['perawatan', 'Perawatan'], ['media-tanam', 'Media Tanam'], ['penyiraman', 'Penyiraman'], ['pencahayaan', 'Pencahayaan'], ['hama-penyakit', 'Hama & Penyakit']] as [$slug, $name]) {
            $cats[$slug] = ArticleCategory::firstOrCreate(['slug' => $slug], ['name' => $name])->id;
        }

        $rows = [
            ['cara-merawat-tanaman-hias-di-dalam-rumah', 'Cara Merawat Tanaman Hias di Dalam Rumah', 'perawatan', '2025-05-12', 'Panduan singkat agar tanaman hias indoor tetap segar dan sehat.',
                "Tanaman indoor membutuhkan cahaya yang cukup. Letakkan dekat jendela dengan cahaya terang tidak langsung, dan putar pot secara berkala agar pertumbuhan merata.\n\nSiram hanya ketika bagian atas media tanam terasa kering. Tusukkan jari sedalam 2–3 cm untuk memeriksanya, dan pastikan pot memiliki lubang drainase.\n\nBersihkan debu pada daun setiap beberapa minggu dengan kain lembap agar tanaman dapat berfotosintesis dengan baik."],
            ['media-tanam-yang-tepat-untuk-tanaman', 'Media Tanam yang Tepat untuk Tanaman', 'media-tanam', '2025-05-08', 'Mengenal campuran media tanam sesuai jenis tanaman.',
                "Media tanam yang baik harus gembur, porous, dan mampu menahan sedikit air. Campuran tanah, cocopeat, dan perlit cocok untuk banyak tanaman hias daun.\n\nKaktus dan sukulen lebih suka media berpasir yang cepat kering. Tanaman buah dalam pot memerlukan tanah subur yang diperkaya kompos atau pupuk kandang.\n\nGanti media tanam setiap 1–2 tahun atau ketika akar mulai memenuhi pot."],
            ['mengatasi-hama-pada-tanaman-hias', 'Mengatasi Hama pada Tanaman Hias', 'hama-penyakit', '2025-05-03', 'Kenali tanda serangan hama dan cara penanganannya.',
                "Periksa bagian bawah daun secara rutin. Bintik kecil, jaring halus, atau daun menguning bisa menandakan kutu putih atau tungau.\n\nPisahkan tanaman yang terserang, lalu bersihkan dengan kain lembap dan semprotkan larutan sabun lembut atau minyak nimba.\n\nJaga sirkulasi udara dan hindari penyiraman berlebihan untuk mencegah hama datang kembali."],
            ['panduan-menyiram-tanaman-indoor', 'Panduan Menyiram Tanaman Indoor', 'penyiraman', '2026-09-20', 'Kapan dan seberapa banyak tanaman perlu disiram.',
                "Kesalahan penyiraman adalah penyebab paling umum tanaman indoor bermasalah. Kebanyakan tanaman lebih mudah pulih dari kekeringan daripada dari media yang selalu basah.\n\nCek kelembapan media dengan jari sebelum menyiram. Siram hingga air keluar dari lubang drainase, lalu buang air yang tertampung di alas pot.\n\nFrekuensi bergantung pada jenis tanaman, ukuran pot, suhu, dan cahaya. Gunakan fitur kalender perawatan Clover untuk mengingatkan jadwal Anda."],
            ['memahami-cahaya-untuk-tanaman-indoor', 'Memahami Cahaya untuk Tanaman Indoor', 'pencahayaan', '2026-09-27', 'Cara menilai intensitas cahaya di ruangan Anda.',
                "Cahaya rendah berarti ruangan terasa terang untuk beraktivitas tetapi tidak terkena sinar langsung. Cahaya sedang biasanya didapat beberapa meter dari jendela atau di dekat jendela dengan tirai tipis. Cahaya tinggi berarti terkena matahari langsung beberapa jam.\n\nDaun yang memucat atau terbakar menandakan cahaya terlalu kuat, sedangkan batang memanjang dan daun kecil menandakan cahaya kurang.\n\nGunakan Plant Finder untuk memilih tanaman yang sesuai dengan kondisi cahaya ruangan Anda."],
        ];
        foreach ($rows as [$slug, $title, $cat, $date, $excerpt, $body]) {
            Article::firstOrCreate(['slug' => $slug], [
                'title' => $title, 'article_category_id' => $cats[$cat], 'published_at' => $date,
                'excerpt' => $excerpt, 'body' => $body, 'is_published' => true,
            ]);
        }
        // Artikel lama yang masih memakai kategori hasil migrasi (Tips/Panduan/Edukasi) dirapikan sekali.
        $legacy = ArticleCategory::whereIn('slug', ['tips', 'panduan', 'edukasi'])->pluck('id')->all();
        $fix = ['cara-merawat-tanaman-hias-di-dalam-rumah' => 'perawatan', 'media-tanam-yang-tepat-untuk-tanaman' => 'media-tanam', 'mengatasi-hama-pada-tanaman-hias' => 'hama-penyakit'];
        foreach ($fix as $slug => $cat) {
            Article::where('slug', $slug)->whereIn('article_category_id', $legacy)->update(['article_category_id' => $cats[$cat]]);
        }
    }

    private function problems(): void
    {
        $rows = [
            ['daun-menguning', 'Daun Menguning', 'leaf', 'Daun berubah kuning, terutama pada daun bagian bawah.', 'Penyiraman berlebihan atau drainase buruk, kekurangan nutrisi, cahaya kurang, atau proses penuaan daun alami.', 'Periksa kelembapan media dan lubang drainase. Kurangi frekuensi siram bila media masih basah, pindahkan ke tempat yang lebih terang, dan beri pupuk ringan bila sudah lama tidak dipupuk. Potong daun yang sudah menguning seluruhnya.'],
            ['ujung-daun-cokelat', 'Ujung Daun Cokelat atau Kering', 'droplet', 'Ujung atau tepi daun mengering dan berwarna cokelat.', 'Udara terlalu kering, penyiraman tidak teratur, penumpukan mineral dari air, atau pupuk berlebih.', 'Jaga jadwal penyiraman yang konsisten, gunakan air yang diendapkan semalam, dan jauhkan dari hembusan AC. Pangkas ujung yang kering mengikuti bentuk daun.'],
            ['pertumbuhan-lambat', 'Pertumbuhan Lambat', 'sprout', 'Tidak ada daun baru dalam waktu lama atau ukuran daun semakin kecil.', 'Cahaya kurang, pot terlalu sempit, media tanam sudah padat, atau tanaman sedang masa istirahat.', 'Pindahkan ke tempat dengan cahaya lebih baik, periksa apakah akar memenuhi pot, dan ganti media tanam. Beri pupuk ringan pada masa tumbuh.'],
            ['batang-lembek', 'Batang atau Pangkal Lembek', 'alert', 'Pangkal batang terasa lunak, berwarna gelap, atau berbau.', 'Media terlalu basah dalam waktu lama yang memicu akar membusuk.', 'Keluarkan tanaman dari pot, buang akar yang lunak dan berbau, lalu tanam di media baru yang kering dan porous. Tahan penyiraman beberapa hari. Bila sebagian besar akar rusak, perbanyak dari bagian yang masih sehat.'],
            ['kutu-putih-dan-tungau', 'Bercak, Bintik, atau Kutu pada Daun', 'eye', 'Bintik kecil, jaring halus, bercak lengket, atau serangga kecil putih di balik daun.', 'Serangan hama seperti kutu putih atau tungau, terutama pada udara yang kering.', 'Pisahkan tanaman dari koleksi lain, bersihkan daun dengan kain lembap, lalu semprot larutan sabun lembut atau minyak nimba. Ulangi tiap beberapa hari dan periksa bagian bawah daun.'],
            ['daun-layu', 'Daun Layu atau Terkulai', 'thermometer', 'Daun lemas dan menunduk meski tampak hijau.', 'Kekurangan air, tetapi bisa juga akibat akar rusak karena terlalu basah, atau perubahan suhu mendadak.', 'Raba media terlebih dahulu. Jika kering, siram perlahan hingga tembus drainase. Jika basah, tunda penyiraman dan periksa akar. Hindari paparan angin atau suhu ekstrem.'],
            ['tanah-berjamur', 'Jamur Putih pada Media Tanam', 'wind', 'Lapisan putih seperti kapas di permukaan tanah.', 'Media terlalu lembap dengan sirkulasi udara kurang.', 'Buang lapisan atas media yang berjamur, kurangi penyiraman, tingkatkan sirkulasi udara, dan pastikan drainase pot lancar.'],
        ];
        foreach ($rows as $i => [$slug, $title, $icon, $sym, $cause, $sug]) {
            PlantProblem::firstOrCreate(['slug' => $slug], [
                'title' => $title, 'icon' => $icon, 'symptoms' => $sym, 'causes' => $cause, 'suggestions' => $sug,
                'sort_order' => $i + 1, 'is_active' => true,
            ]);
        }
    }

    private function banners(): void
    {
        Banner::firstOrCreate(['type' => 'announcement', 'title' => 'Gratis ongkir Reguler untuk belanja minimal Rp300.000'], [
            'text' => 'Pelanggan baru: gunakan kode WELCOME10 untuk potongan 10 persen.',
            'link_label' => 'Belanja sekarang', 'link_url' => '/tanaman', 'is_active' => true, 'sort_order' => 1,
        ]);

        $inspo = [
            ['Ruang Kerja', 'Tanaman kecil yang menenangkan pandangan di meja kerja.', '/tanaman?kategori=meja-kerja'],
            ['Ruang Tamu', 'Tanaman besar sebagai titik fokus ruangan.', '/tanaman?kategori=hias-daun'],
            ['Kamar Tidur', 'Tanaman cahaya rendah yang tenang dan mudah dirawat.', '/plant-finder'],
            ['Area Komersial', 'Penataan tanaman untuk kafe, kantor, dan toko.', '/jasa'],
        ];
        foreach ($inspo as $i => [$title, $text, $url]) {
            Banner::firstOrCreate(['type' => 'inspiration', 'title' => $title], [
                'text' => $text, 'link_label' => 'Lihat ide', 'link_url' => $url, 'is_active' => true, 'sort_order' => $i + 1,
            ]);
        }

        $promos = [
            ['Paket Perawatan Bulanan', 'Kunjungan rutin agar tanaman kantor dan rumah selalu terawat.', 'Lihat layanan', '/jasa'],
            ['Koleksi Tanaman Meja Kerja', 'Pilihan tanaman kecil yang menyegarkan area kerja.', 'Jelajahi koleksi', '/tanaman?kategori=meja-kerja'],
        ];
        foreach ($promos as $i => [$title, $text, $label, $url]) {
            Banner::firstOrCreate(['type' => 'promo', 'title' => $title], [
                'text' => $text, 'link_label' => $label, 'link_url' => $url, 'is_active' => true, 'sort_order' => $i + 1,
            ]);
        }
    }

    private function coupons(): void
    {
        Coupon::firstOrCreate(['code' => 'WELCOME10'], [
            'description' => 'Potongan 10 persen untuk pembelian pertama', 'type' => 'percent', 'value' => 10,
            'min_subtotal' => 100000, 'max_discount' => 50000, 'is_active' => true,
        ]);
        Coupon::firstOrCreate(['code' => 'HEMAT25K'], [
            'description' => 'Potongan Rp25.000 untuk belanja minimal Rp250.000', 'type' => 'fixed', 'value' => 25000,
            'min_subtotal' => 250000, 'is_active' => true,
        ]);
    }
}
