<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Plant;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->categories();
        $this->plants();
        $this->products();
    }

    private function categories(): void
    {
        $rows = [
            ['indoor', 'Tanaman Indoor', 'home', 'Tanaman yang nyaman hidup di dalam ruangan.'],
            ['outdoor', 'Tanaman Outdoor', 'sun', 'Untuk teras, taman, dan area terbuka.'],
            ['sukulen', 'Sukulen', 'sprout', 'Mungil, tahan kering, mudah dirawat.'],
            ['kaktus', 'Kaktus', 'cactus', 'Pilihan tepat untuk pencinta tanaman minim perawatan.'],
            ['hias-daun', 'Tanaman Hias Daun', 'leaf', 'Daun berbentuk dan berwarna menawan.'],
            ['berbunga', 'Tanaman Berbunga', 'flower', 'Bunga cantik untuk mempercantik ruangan.'],
            ['meja-kerja', 'Tanaman Meja Kerja', 'desk', 'Ukuran kecil untuk meja kerja dan rak.'],
            ['perlengkapan', 'Perlengkapan & Media Tanam', 'pot', 'Pot, media tanam, dan pupuk.'],
        ];
        foreach ($rows as $i => [$slug, $name, $icon, $desc]) {
            $c = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'icon' => $icon, 'sort_order' => $i + 1, 'is_active' => true]);
            if (! $c->description) {
                $c->update(['description' => $desc]);
            }
        }
    }

    private function plants(): void
    {
        // slug, nama, latin, cahaya, ket.cahaya, perawatan, siram(hari), ket.siram, kelembapan, suhu, media, pupuk, penempatan, aman hewan, deskripsi, tips
        $rows = [
            ['monstera', 'Monstera Deliciosa', 'Monstera deliciosa', 'sedang', 'Cahaya terang tidak langsung', 'mudah', 7, 'Siram saat 2–3 cm tanah bagian atas kering', 'Sedang hingga tinggi (50–70%)', '18–30 °C', 'Tanah gembur, cocopeat, dan perlit', 'Pupuk daun setiap 4–6 minggu saat masa tumbuh', 'ruang tamu,kantor', false, 'Daun besar berlubang khas yang menjadikannya pusat perhatian ruangan.', 'Bersihkan daun dari debu dan beri tiang rambat agar tumbuh tegak.'],
            ['aglaonema', 'Aglaonema', 'Aglaonema commutatum', 'rendah', 'Cahaya redup hingga sedang', 'mudah', 4, 'Jaga media tetap lembap, tidak tergenang', 'Sedang (40–60%)', '20–30 °C', 'Tanah humus dan sekam bakar', 'Pupuk seimbang sebulan sekali', 'kamar,ruang tamu,kantor', false, 'Daun berpola merah muda yang tahan di ruangan minim cahaya.', 'Hindari sinar matahari langsung agar warna daun tidak pudar.'],
            ['sansevieria', 'Sansevieria', 'Dracaena trifasciata', 'rendah', 'Redup hingga terang', 'mudah', 10, 'Siram jarang, biarkan media kering di antara penyiraman', 'Rendah hingga sedang', '15–32 °C', 'Tanah berpasir yang porous', 'Pupuk 2–3 bulan sekali', 'kamar,kantor,ruang tamu', false, 'Sangat tangguh dan cocok untuk pemula.', 'Kelebihan air adalah penyebab utama masalah pada tanaman ini.'],
            ['philodendron', 'Philodendron', 'Philodendron hederaceum', 'sedang', 'Cahaya terang tidak langsung', 'mudah', 4, 'Siram saat tanah bagian atas mulai kering', 'Sedang', '18–30 °C', 'Tanah gembur dan perlit', 'Pupuk daun sebulan sekali', 'ruang tamu,kantor', false, 'Daun hijau mengilap, tumbuh subur di rak maupun digantung.', 'Pangkas ujung sulur agar tumbuh lebih rimbun.'],
            ['lavender', 'Lavender', 'Lavandula angustifolia', 'tinggi', 'Sinar matahari penuh', 'sedang', 3, 'Siram saat media kering, hindari genangan', 'Rendah', '15–28 °C', 'Tanah berpasir dan kompos', 'Pupuk ringan saat musim berbunga', 'teras', false, 'Bunga ungu beraroma menenangkan dan menarik kupu-kupu.', 'Butuh sinar matahari 6 jam sehari dan drainase yang baik.'],
            ['janda-bolong', 'Janda Bolong', 'Monstera adansonii', 'sedang', 'Cahaya terang tidak langsung', 'mudah', 5, 'Siram saat tanah bagian atas kering', 'Sedang hingga tinggi', '18–30 °C', 'Tanah gembur dan cocopeat', 'Pupuk daun 4–6 minggu sekali', 'ruang tamu,kamar', false, 'Daun berlubang cantik dan sulur yang bisa dirambatkan.', 'Beri media rambat agar daun tumbuh lebih besar.'],
            ['kaktus-mini', 'Kaktus Mini', null, 'tinggi', 'Sinar matahari penuh', 'mudah', 14, 'Siram sangat jarang', 'Rendah', '20–35 °C', 'Campuran pasir dan tanah kaktus', 'Pupuk kaktus 2 bulan sekali', 'kantor,teras', null, 'Mungil dan hemat tempat, cocok untuk meja kerja dan hadiah.', 'Pastikan pot memiliki lubang drainase.'],
            ['peace-lily', 'Peace Lily', 'Spathiphyllum wallisii', 'rendah', 'Redup hingga sedang', 'mudah', 4, 'Jaga media lembap; daun layu menandakan butuh air', 'Sedang hingga tinggi', '18–28 °C', 'Tanah humus yang lembap', 'Pupuk seimbang sebulan sekali', 'kamar,ruang tamu,kantor', false, 'Bunga putih elegan, tahan teduh.', 'Bersihkan daun secara rutin agar tampak segar.'],
            ['jeruk-nipis', 'Jeruk Nipis (Pot)', 'Citrus aurantiifolia', 'tinggi', 'Sinar matahari penuh', 'sedang', 3, 'Siram teratur, jaga media tetap lembap', 'Sedang', '22–35 °C', 'Tanah subur dengan pupuk kandang', 'Pupuk NPK setiap 1–2 bulan', 'teras', null, 'Bibit okulasi yang bisa berbuah di dalam pot.', 'Pindahkan ke pot lebih besar setiap 1–2 tahun.'],
            ['sukulen-set', 'Sukulen Mini', null, 'tinggi', 'Cahaya terang', 'mudah', 10, 'Siram jarang, biarkan media kering', 'Rendah', '18–32 °C', 'Campuran khusus sukulen', 'Pupuk sukulen 2 bulan sekali', 'kantor,kamar', null, 'Koleksi sukulen mungil yang mudah dirawat.', 'Beri cahaya terang agar bentuknya tidak memanjang.'],
            ['zz-plant', 'ZZ Plant', 'Zamioculcas zamiifolia', 'rendah', 'Redup hingga sedang', 'mudah', 14, 'Siram setiap 2 minggu atau saat media benar-benar kering', 'Rendah', '18–30 °C', 'Tanah porous', 'Pupuk 2–3 bulan sekali', 'kantor,kamar,ruang tamu', false, 'Daun mengilap dan sangat tahan terhadap kelalaian.', 'Jangan terlalu sering disiram.'],
            ['pothos', 'Pothos', 'Epipremnum aureum', 'rendah', 'Redup hingga terang tidak langsung', 'mudah', 5, 'Siram saat tanah bagian atas kering', 'Sedang', '15–30 °C', 'Tanah gembur dan cocopeat', 'Pupuk daun sebulan sekali', 'kantor,kamar,dapur', false, 'Sulur menjuntai yang mudah diperbanyak.', 'Bisa ditumbuhkan dalam air untuk sementara.'],
            ['lidah-buaya', 'Lidah Buaya', 'Aloe vera', 'tinggi', 'Cahaya terang atau matahari pagi', 'mudah', 10, 'Siram saat media kering', 'Rendah', '18–35 °C', 'Campuran pasir dan tanah', 'Pupuk sukulen 2–3 bulan sekali', 'teras,dapur', false, 'Tanaman serbaguna yang tahan kering.', 'Pastikan drainase sangat baik.'],
            ['peperomia', 'Peperomia', 'Peperomia obtusifolia', 'sedang', 'Cahaya terang tidak langsung', 'mudah', 7, 'Siram saat media setengah kering', 'Sedang', '18–28 °C', 'Tanah gembur dan perlit', 'Pupuk ringan sebulan sekali', 'kantor,kamar,ruang tamu', true, 'Daun tebal berkilau, ukuran ringkas, dan aman untuk hewan.', 'Cocok untuk meja kerja.'],
            ['calathea', 'Calathea', 'Goeppertia', 'sedang', 'Cahaya terang tidak langsung', 'sulit', 3, 'Gunakan air bebas kaporit, jaga lembap', 'Tinggi (60–80%)', '18–27 °C', 'Tanah humus yang lembap', 'Pupuk ringan 2 minggu sekali', 'ruang tamu,kamar', true, 'Pola daun indah yang bergerak mengikuti cahaya, tetapi menuntut kelembapan.', 'Sensitif pada air dengan kandungan mineral tinggi.'],
            ['pakis-boston', 'Pakis Boston', 'Nephrolepis exaltata', 'sedang', 'Cahaya terang tidak langsung', 'sedang', 3, 'Jaga media selalu lembap', 'Tinggi', '16–27 °C', 'Tanah humus kaya organik', 'Pupuk seimbang sebulan sekali', 'teras,ruang tamu', true, 'Rimbun dan aman untuk hewan peliharaan.', 'Semprot daun saat udara kering.'],
            ['spider-plant', 'Spider Plant', 'Chlorophytum comosum', 'sedang', 'Cahaya terang tidak langsung', 'mudah', 5, 'Siram saat tanah bagian atas kering', 'Sedang', '15–28 °C', 'Tanah gembur', 'Pupuk sebulan sekali', 'kantor,kamar,dapur', true, 'Menghasilkan anakan yang mudah dipisahkan.', 'Ujung daun cokelat sering akibat kualitas air.'],
        ];

        foreach ($rows as $r) {
            [$slug, $name, $latin, $light, $lightText, $care, $days, $waterText, $humidity, $temp, $soil, $fert, $place, $pet, $desc, $tips] = $r;
            $p = Plant::firstOrNew(['slug' => $slug]);
            // Hanya isi bila baru ATAU baris hasil migrasi yang belum dilengkapi (latin_name masih kosong).
            if (! $p->exists || ($p->latin_name === null && $p->tips === null)) {
                $p->fill([
                    'name' => $name, 'latin_name' => $latin, 'light_level' => $light, 'light_text' => $lightText,
                    'care_level' => $care, 'water_days' => $days, 'water_text' => $waterText, 'humidity' => $humidity,
                    'temperature' => $temp, 'soil' => $soil, 'fertilizer' => $fert, 'placements' => $place,
                    'pet_safe' => $pet, 'description' => $desc, 'tips' => $tips,
                ])->save();
            }
        }
    }

    private function products(): void
    {
        $cat = Category::pluck('id', 'slug');
        // slug, nama, kategori, harga, stok, ukuran, plant slug, deskripsi
        $rows = [
            ['monstera', 'Monstera Deliciosa', 'hias-daun', 125000, 24, '40–60 cm', 'monstera', 'Daun besar berlubang khas yang menjadikannya pusat perhatian ruangan.'],
            ['aglaonema', 'Aglaonema', 'hias-daun', 85000, 31, '30–45 cm', 'aglaonema', 'Daun merah muda berpola, mudah dirawat dan tahan ruangan minim cahaya.'],
            ['sansevieria', 'Sansevieria', 'indoor', 75000, 40, '35–60 cm', 'sansevieria', 'Dikenal tangguh dan membantu menyegarkan suasana ruangan.'],
            ['philodendron', 'Philodendron', 'hias-daun', 95000, 18, '30–50 cm', 'philodendron', 'Daun hijau mengilap yang tumbuh subur dan cocok untuk rak maupun gantung.'],
            ['lavender', 'Lavender', 'berbunga', 65000, 22, '25–40 cm', 'lavender', 'Bunga ungu beraroma menenangkan yang menarik kupu-kupu ke taman.'],
            ['janda-bolong', 'Janda Bolong', 'hias-daun', 150000, 12, '45–70 cm', 'janda-bolong', 'Daun berlubang cantik, favorit kolektor.'],
            ['kaktus-mini', 'Kaktus Mini', 'kaktus', 45000, 60, '8–15 cm', 'kaktus-mini', 'Mungil dan hemat tempat, ideal untuk meja kerja dan hadiah.'],
            ['peace-lily', 'Peace Lily', 'berbunga', 85000, 27, '40–60 cm', 'peace-lily', 'Bunga putih elegan, tahan teduh.'],
            ['jeruk-nipis', 'Jeruk Nipis Pot', 'outdoor', 220000, 9, '80–120 cm', 'jeruk-nipis', 'Bibit okulasi yang bisa berbuah di pot, praktis untuk teras rumah.'],
            ['sukulen-set', 'Set Sukulen Mini', 'sukulen', 55000, 35, '5–10 cm', 'sukulen-set', 'Paket tiga sukulen mungil dalam pot keramik, siap dipajang.'],
            ['zz-plant', 'ZZ Plant', 'indoor', 110000, 20, '35–55 cm', 'zz-plant', 'Daun mengilap dan sangat tahan terhadap kelalaian.'],
            ['pothos-golden', 'Pothos Golden', 'meja-kerja', 55000, 36, '20–40 cm', 'pothos', 'Sulur menjuntai yang cantik untuk rak dan meja kerja.'],
            ['lidah-buaya', 'Lidah Buaya', 'sukulen', 40000, 28, '20–35 cm', 'lidah-buaya', 'Tanaman serbaguna yang tahan kering.'],
            ['peperomia', 'Peperomia', 'meja-kerja', 60000, 25, '15–25 cm', 'peperomia', 'Daun tebal berkilau dan aman untuk hewan peliharaan.'],
            ['pot-keramik-minimalis', 'Pot Keramik Minimalis', 'perlengkapan', 65000, 30, 'Diameter 15 cm', null, 'Pot keramik dengan lubang drainase dan alas.'],
            ['media-tanam-premium', 'Media Tanam Premium 5 L', 'perlengkapan', 45000, 50, '5 liter', null, 'Campuran tanah gembur, cocopeat, dan perlit untuk tanaman hias.'],
            ['pupuk-organik-cair', 'Pupuk Organik Cair 500 ml', 'perlengkapan', 38000, 45, '500 ml', null, 'Pupuk cair untuk mendukung pertumbuhan daun dan akar.'],
        ];

        foreach ($rows as [$slug, $name, $c, $price, $stock, $size, $plantSlug, $desc]) {
            $plantId = $plantSlug ? Plant::where('slug', $plantSlug)->value('id') : null;
            $p = Product::firstOrCreate(['slug' => $slug], [
                'name' => $name, 'category_id' => $cat[$c] ?? null, 'plant_id' => $plantId,
                'price' => $price, 'stock' => $stock, 'size' => $size, 'description' => $desc,
                'rating_avg' => 0, 'reviews_count' => 0, 'is_active' => true,
            ]);
            if (! $p->category_id && isset($cat[$c])) {
                $p->update(['category_id' => $cat[$c]]);
            }
            if (! $p->plant_id && $plantId) {
                $p->update(['plant_id' => $plantId]);
            }
        }

        // Penanda unggulan hanya diberikan sekali (jika admin belum menentukan sendiri).
        if (! Product::where('is_featured', true)->exists()) {
            Product::whereIn('slug', ['monstera', 'aglaonema', 'sansevieria', 'janda-bolong', 'lavender', 'peace-lily', 'kaktus-mini', 'pothos-golden'])->update(['is_featured' => true]);
        }
        if (! Product::where('is_plant_of_week', true)->exists()) {
            Product::where('slug', 'sansevieria')->update(['is_plant_of_week' => true]);
        }
    }
}
