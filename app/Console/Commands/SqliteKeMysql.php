<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Memindahkan SELURUH isi database/database.sqlite ke MySQL (Laragon), lalu membuktikan
 * bahwa isinya sama: jumlah baris DAN isi setiap baris dibandingkan satu per satu.
 *
 * - File SQLite hanya DIBACA, tidak pernah diubah.
 * - Struktur tabel MySQL dibuat oleh `php artisan migrate` (bukan oleh perintah ini).
 * - Aman dijalankan berulang dengan opsi --timpa.
 */
class SqliteKeMysql extends Command
{
    protected $signature = 'clover:sqlite-ke-mysql
        {--sqlite= : Lokasi file SQLite (bawaan: database/database.sqlite)}
        {--timpa : Kosongkan dulu tabel MySQL yang sudah berisi, lalu salin ulang}
        {--cek : Hanya membandingkan SQLite dengan MySQL, tanpa menyalin apa pun}';

    protected $description = 'Memindahkan seluruh isi database SQLite ke MySQL lalu memverifikasi bahwa isinya sama';

    private Connection $src;

    private Connection $dst;

    /** @var array<string, array<string, array{tipe: string, ekstra: string}>> */
    private array $kolomInfo = [];

    public function handle(): int
    {
        try {
            return $this->jalankan();
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('GAGAL: '.$e->getMessage());
            $this->line('File SQLite Anda TIDAK diubah. Perbaiki penyebabnya, lalu jalankan ulang dengan --timpa.');

            return self::FAILURE;
        }
    }

    private function jalankan(): int
    {
        /* ---------- 1. Pastikan tujuan benar-benar MySQL ---------- */
        $this->dst = DB::connection();
        if (! in_array($this->dst->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('Koneksi bawaan masih "'.$this->dst->getDriverName().'", bukan MySQL.');
            $this->line('Ubah .env menjadi DB_CONNECTION=mysql, lalu jalankan: php artisan config:clear');

            return self::FAILURE;
        }
        $this->dst->getPdo(); // melempar pesan jelas bila MySQL mati / database belum dibuat / kata sandi salah

        /* ---------- 2. Buka SQLite dengan jalur eksplisit ---------- */
        // Sengaja tidak memakai koneksi "sqlite" bawaan, karena DB_DATABASE di .env sekarang berisi nama database MySQL.
        $path = $this->option('sqlite') ?: database_path('database.sqlite');
        if (! is_file($path)) {
            $this->error('File SQLite tidak ditemukan: '.$path);

            return self::FAILURE;
        }
        Config::set('database.connections.sqlite_sumber', [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite_sumber');
        $this->src = DB::connection('sqlite_sumber');

        $this->info('Sumber : '.$path);
        $this->info('Tujuan : MySQL database "'.$this->dst->getDatabaseName().'"');
        $this->newLine();

        /* ---------- 3. Periksa struktur sebelum menyentuh data apa pun ---------- */
        $tabel = collect($this->src->select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name"))
            ->pluck('name')
            ->reject(fn ($n) => $n === 'migrations')   // tabel migrations diisi oleh php artisan migrate
            ->values()
            ->all();

        $rencana = [];
        $masalah = [];
        $builder = $this->dst->getSchemaBuilder();

        foreach ($tabel as $t) {
            if (! $builder->hasTable($t)) {
                $masalah[] = "Tabel \"$t\" belum ada di MySQL (jalankan: php artisan migrate).";

                continue;
            }

            $kolomSqlite = array_map(fn ($c) => $c->name, $this->src->select('pragma table_info("'.$t.'")'));
            $kolomMysql = $builder->getColumnListing($t);
            $hilang = array_diff($kolomSqlite, $kolomMysql);
            if ($hilang) {
                $masalah[] = "Tabel \"$t\": kolom ".implode(', ', $hilang).' ada di SQLite tetapi tidak ada di MySQL.';

                continue;
            }
            $rencana[$t] = array_values(array_intersect($kolomSqlite, $kolomMysql));

            $rows = $this->dst->select(
                'select column_name as nama, data_type as tipe, extra as ekstra from information_schema.columns where table_schema = database() and table_name = ?',
                [$t]
            );
            foreach ($rows as $r) {
                $this->kolomInfo[$t][strtolower($r->nama)] = ['tipe' => strtolower($r->tipe), 'ekstra' => strtolower((string) $r->ekstra)];
            }
        }

        if ($masalah) {
            $this->error('Struktur belum siap, tidak ada data yang disentuh:');
            foreach ($masalah as $m) {
                $this->line(' - '.$m);
            }

            return self::FAILURE;
        }

        /* ---------- 4. Pastikan MySQL masih kosong (atau --timpa) ---------- */
        if (! $this->option('cek')) {
            $terisi = [];
            foreach (array_keys($rencana) as $t) {
                $n = $this->dst->table($t)->count();
                if ($n > 0) {
                    $terisi[] = "$t ($n baris)";
                }
            }
            if ($terisi && ! $this->option('timpa')) {
                $this->error('Tabel MySQL ini sudah berisi data: '.implode(', ', $terisi));
                $this->line('Jika memang ingin menimpa, jalankan: php artisan clover:sqlite-ke-mysql --timpa');

                return self::FAILURE;
            }

            $this->info('Menyalin data ...');
            $this->salin($rencana);
        }

        /* ---------- 5. Verifikasi ---------- */
        return $this->verifikasi($rencana) ? self::SUCCESS : self::FAILURE;
    }

    private function salin(array $rencana): void
    {
        $this->dst->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($rencana as $t => $kolom) {
                if ($this->option('timpa')) {
                    $this->dst->statement('TRUNCATE TABLE `'.$t.'`');
                }

                $jumlah = 0;
                $this->dst->transaction(function () use ($t, $kolom, &$jumlah) {
                    $batch = [];
                    foreach ($this->src->table($t)->select($kolom)->cursor() as $baris) {
                        $batch[] = $this->siapkan($t, (array) $baris);
                        if (count($batch) >= 200) {
                            $this->dst->table($t)->insert($batch);
                            $jumlah += count($batch);
                            $batch = [];
                        }
                    }
                    if ($batch) {
                        $this->dst->table($t)->insert($batch);
                        $jumlah += count($batch);
                    }
                });

                $this->line(sprintf('  %-28s %5d baris', $t, $jumlah));
            }

            $this->aturAutoIncrement($rencana);
        } finally {
            $this->dst->statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /** Kolom bertipe DATE di MySQL hanya menyimpan tanggal; SQLite kadang menyimpan "2026-10-20 00:00:00". */
    private function siapkan(string $t, array $baris): array
    {
        foreach ($baris as $k => $v) {
            if (is_string($v) && strlen($v) > 10 && ($this->kolomInfo[$t][$k]['tipe'] ?? '') === 'date') {
                $baris[$k] = substr($v, 0, 10);
            }
        }

        return $baris;
    }

    /** Nomor id berikutnya dibuat sama dengan SQLite (sqlite_sequence), jadi id baru tidak "mundur". */
    private function aturAutoIncrement(array $rencana): void
    {
        foreach (array_keys($rencana) as $t) {
            if (! str_contains($this->kolomInfo[$t]['id']['ekstra'] ?? '', 'auto_increment')) {
                continue;
            }
            $seq = (int) ($this->src->table('sqlite_sequence')->where('name', $t)->value('seq') ?? 0);
            $max = (int) $this->dst->table($t)->max('id');
            $this->dst->statement('ALTER TABLE `'.$t.'` AUTO_INCREMENT = '.(max($seq, $max) + 1));
        }
    }

    private function verifikasi(array $rencana): bool
    {
        $this->newLine();
        $this->info('Verifikasi: membandingkan isi setiap baris SQLite dengan MySQL ...');

        $ringkasan = [];
        $semuaSama = true;

        foreach ($rencana as $t => $kolom) {
            $urut = $kolom;
            sort($urut);

            $a = $this->tandaTangan($this->src->table($t)->select($kolom)->cursor(), $t, $urut, true);
            $b = $this->tandaTangan($this->dst->table($t)->select($kolom)->cursor(), $t, $urut, false);

            $sama = $a['n'] === $b['n'] && $a['hash'] === $b['hash'];
            $semuaSama = $semuaSama && $sama;
            $ringkasan[] = [$t, $a['n'], $b['n'], $sama ? 'SAMA' : 'BEDA  <<<'];
        }

        $this->table(['Tabel', 'Baris SQLite', 'Baris MySQL', 'Isi'], $ringkasan);

        // Relasi (foreign key) tidak boleh menunjuk ke baris yang tidak ada.
        $yatim = 0;
        $fks = $this->dst->select(
            'select table_name as tbl, column_name as kol, referenced_table_name as ref_tbl, referenced_column_name as ref_kol
             from information_schema.key_column_usage
             where table_schema = database() and referenced_table_name is not null'
        );
        foreach ($fks as $fk) {
            $n = $this->dst->table($fk->tbl.' as c')
                ->leftJoin($fk->ref_tbl.' as p', 'p.'.$fk->ref_kol, '=', 'c.'.$fk->kol)
                ->whereNotNull('c.'.$fk->kol)
                ->whereNull('p.'.$fk->ref_kol)
                ->count();
            if ($n > 0) {
                $yatim += $n;
                $this->warn("Relasi rusak: {$fk->tbl}.{$fk->kol} menunjuk ke {$fk->ref_tbl} yang tidak ada ($n baris).");
            }
        }

        // Semua file migrasi harus tercatat di MySQL, supaya `php artisan migrate` berikutnya tidak membuat ulang tabel.
        $berkas = count(glob(database_path('migrations/*.php')) ?: []);
        $tercatat = $this->dst->table('migrations')->count();
        $migrasiOk = $berkas === $tercatat;
        if (! $migrasiOk) {
            $this->warn("File migrasi: $berkas, tercatat di MySQL: $tercatat. Jalankan: php artisan migrate");
        }

        $this->newLine();
        if ($semuaSama && $yatim === 0 && $migrasiOk) {
            $this->info('BERHASIL: seluruh isi MySQL sama dengan SQLite, relasi utuh, migrasi lengkap.');

            return true;
        }

        $this->error('Ada yang belum sama. Jalankan ulang dengan: php artisan clover:sqlite-ke-mysql --timpa');

        return false;
    }

    /** @return array{n: int, hash: string} */
    private function tandaTangan(iterable $baris, string $t, array $kolom, bool $dariSqlite): array
    {
        $tanda = [];
        foreach ($baris as $r) {
            $r = (array) $r;
            if ($dariSqlite) {
                $r = $this->siapkan($t, $r);
            }
            $norm = [];
            foreach ($kolom as $k) {
                $norm[$k] = $this->normal($r[$k] ?? null, $this->kolomInfo[$t][$k]['tipe'] ?? '');
            }
            $tanda[] = sha1((string) json_encode($norm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
        }
        sort($tanda);

        return ['n' => count($tanda), 'hash' => sha1(implode('', $tanda))];
    }

    /** Menyamakan format yang wajar berbeda antar database (angka desimal, urutan kunci JSON) sebelum dibandingkan. */
    private function normal(mixed $v, string $tipe): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            $v = $v ? 1 : 0;
        }

        if (in_array($tipe, ['decimal', 'float', 'double'], true)) {
            return rtrim(rtrim(number_format((float) $v, 6, '.', ''), '0'), '.');
        }

        $s = (string) $v;
        if ($s !== '' && ($s[0] === '{' || $s[0] === '[')) {
            $j = json_decode($s, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
                $this->urutkanKunci($j);

                return (string) json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $s;
    }

    private function urutkanKunci(array &$a): void
    {
        ksort($a);
        foreach ($a as &$v) {
            if (is_array($v)) {
                $this->urutkanKunci($v);
            }
        }
        unset($v);
    }
}
