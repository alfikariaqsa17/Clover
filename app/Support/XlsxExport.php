<?php

namespace App\Support;

use DateTimeInterface;
use ZipArchive;

/**
 * Pembuat file Excel (.xlsx) bertema Clover tanpa library tambahan.
 *
 * Hasil: banner hijau + logo Clover, kartu ringkasan, tabel dengan header hijau,
 * baris selang-seling, SEMUA sel berborder (garis menyatu), badge status berwarna,
 * baris total (rumus SUM), filter otomatis, dan pengaturan cetak landscape.
 *
 * Bila ekstensi PHP "zip" tidak aktif (atau diminta ?format=csv), otomatis
 * jatuh ke CSV UTF-8 biasa supaya ekspor tidak pernah gagal.
 */
class XlsxExport
{
    private const FOREST = '1C4630';   // sama persis dengan warna latar logo
    private const SAGE_SOFT = 'DFE9DC';
    private const ZEBRA = 'F3F8F0';
    private const LINE = 'B9CBB5';
    private const INK = '14251A';

    private string $sheetName;
    private string $title = '';
    private string $meta = '';
    private array $summary = [];
    private array $columns = [];
    private array $data = [];
    private bool $totals = true;

    // registry gaya
    private array $fonts = [];
    private array $fills = [];
    private array $borders = [];
    private array $numFmts = [];
    private array $xfs = [];

    public function __construct(string $sheetName = 'Laporan')
    {
        $clean = preg_replace('/[\[\]\:\*\?\/\\\\\']/', ' ', $sheetName) ?: 'Laporan';
        $this->sheetName = mb_substr(trim($clean), 0, 31) ?: 'Laporan';
    }

    public static function make(string $sheetName = 'Laporan'): self
    {
        return new self($sheetName);
    }

    public function banner(string $title, string $meta = ''): self
    {
        $this->title = $title;
        $this->meta = $meta;

        return $this;
    }

    /** @param array<int, array{0:string,1:mixed,2?:string}> $items [label, nilai, tipe(text|money|int)] */
    public function summary(array $items): self
    {
        $this->summary = $items;

        return $this;
    }

    /**
     * @param array<int, array{label:string,width?:int,type?:string,sum?:bool,badge?:array}> $columns
     *        type: text | money | int | datetime
     * @param array<int, array<int, mixed>> $rows
     */
    public function table(array $columns, array $rows, bool $totals = true): self
    {
        $this->columns = $columns;
        $this->data = $rows;
        $this->totals = $totals;

        return $this;
    }

    /**
     * format: html (tampil di browser, default) | htmlfile (unduh .html) | xlsx | csv
     */
    public function download(string $baseName, ?string $format = null): mixed
    {
        $format = in_array($format, ['html', 'htmlfile', 'xlsx', 'csv'], true) ? $format : 'html';

        if ($format === 'html') {
            return response($this->html(true), 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']);
        }
        if ($format === 'htmlfile') {
            return response($this->html(false), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $baseName . '.html"',
            ]);
        }
        if ($format === 'csv' || ! class_exists(ZipArchive::class)) {
            return $this->csvResponse($baseName . '.csv');
        }

        $bin = $this->build();

        return response($bin, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $baseName . '.xlsx"',
            'Content-Length' => (string) strlen($bin),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Laporan HTML (bisa dibuka di semua browser, dicetak, atau di-PDF)   */
    /* ------------------------------------------------------------------ */

    private function html(bool $toolbar): string
    {
        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
        $logoPath = resource_path('export/clover-logo.png');
        $logo = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath)) : '';

        $fmt = function ($v, string $type) use ($e, $rp) {
            if ($v === null || $v === '') {
                return '';
            }
            return match ($type) {
                'money' => $rp($v),
                'int' => number_format((float) $v, 0, ',', '.'),
                'datetime' => $v instanceof DateTimeInterface ? $v->format('d/m/Y H:i') : $e($v),
                default => $e($v),
            };
        };

        $cards = '';
        foreach ($this->summary as $item) {
            $type = $item[2] ?? 'text';
            $cards .= '<div class="card"><span>' . $e($item[0]) . '</span><b>' . ($type === 'text' ? $e($item[1]) : $fmt($item[1], $type)) . '</b></div>';
        }

        $head = '';
        foreach ($this->columns as $col) {
            $head .= '<th class="t-' . ($col['type'] ?? 'text') . '">' . $e($col['label']) . '</th>';
        }

        $body = '';
        $sums = [];
        foreach ($this->data as $row) {
            $body .= '<tr>';
            foreach ($this->columns as $c => $col) {
                $type = $col['type'] ?? 'text';
                $v = $row[$c] ?? null;
                if (in_array($type, ['money', 'int'], true)) {
                    $sums[$c] = ($sums[$c] ?? 0) + (float) ($v ?? 0);
                }
                if (isset($col['badge']) && $v !== null && $v !== '') {
                    $bd = $col['badge'][(string) $v] ?? ['fg' => self::INK, 'bg' => 'EEEEEE'];
                    $body .= '<td class="t-badge"><span class="badge" style="color:#' . $bd['fg'] . ';background:#' . $bd['bg'] . '">' . $e($v) . '</span></td>';
                } else {
                    $body .= '<td class="t-' . $type . '">' . $fmt($v, $type) . '</td>';
                }
            }
            $body .= '</tr>';
        }
        if (! $this->data) {
            $body = '<tr><td class="empty" colspan="' . max(1, count($this->columns)) . '">Tidak ada data untuk ditampilkan.</td></tr>';
        }

        $foot = '';
        if ($this->totals && $this->data) {
            $foot = '<tfoot><tr>';
            foreach ($this->columns as $c => $col) {
                $type = $col['type'] ?? 'text';
                if (! empty($col['sum']) && in_array($type, ['money', 'int'], true)) {
                    $foot .= '<td class="t-' . $type . '">' . $fmt($sums[$c] ?? 0, $type) . '</td>';
                } else {
                    $foot .= '<td>' . ($c === 0 ? 'TOTAL' : '') . '</td>';
                }
            }
            $foot .= '</tr></tfoot>';
        }

        $bar = '';
        if ($toolbar) {
            $req = request();
            $link = fn (string $f) => $e($req->fullUrlWithQuery(['format' => $f]));
            $bar = '<div class="bar no-print"><div class="bar-in">'
                . '<button type="button" class="b primary" onclick="window.print()">🖨 Cetak / Simpan PDF</button>'
                . '<a class="b" href="' . $link('xlsx') . '">⬇ Excel (.xlsx)</a>'
                . '<a class="b" href="' . $link('csv') . '">⬇ CSV</a>'
                . '<a class="b" href="' . $link('htmlfile') . '">⬇ Simpan file .html</a>'
                . '<span class="hint">Tip: pada jendela cetak pilih “Simpan sebagai PDF”.</span>'
                . '</div></div>';
        }

        $css = <<<'CSS'
*{box-sizing:border-box}
html{-webkit-print-color-adjust:exact;print-color-adjust:exact}
body{margin:0;background:#eef2ea;color:#14251a;font:14px/1.5 "Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif}
.bar{position:sticky;top:0;z-index:5;background:#fff;border-bottom:1px solid #d5dfd1;box-shadow:0 2px 10px rgba(20,37,26,.08)}
.bar-in{max-width:1180px;margin:0 auto;padding:10px 20px;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.b{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:999px;border:1px solid #b9cbb5;background:#fff;color:#1c4630;font:600 13px "Segoe UI",system-ui,sans-serif;text-decoration:none;cursor:pointer}
.b:hover{background:#f0f6ec}.b.primary{background:#1c4630;border-color:#1c4630;color:#fff}.b.primary:hover{background:#285c40}
.hint{margin-left:auto;font-size:12px;color:#5b6a60}
.sheet{max-width:1180px;margin:24px auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 14px 40px rgba(20,37,26,.14)}
.hero{display:flex;align-items:center;gap:22px;padding:26px 34px;background:linear-gradient(120deg,#1c4630,#285c40);color:#fff}
.hero img{width:92px;height:92px;border-radius:20px;flex:none}
.brand{font:700 30px/1.1 Georgia,"Times New Roman",serif;letter-spacing:.04em}
.tag{font-size:11px;font-weight:700;letter-spacing:.2em;color:#a3bba0;margin-top:2px}
.title{font-size:20px;font-weight:700;color:#9adba0;margin-top:10px}
.meta{font-size:12.5px;color:#dfe9dc;margin-top:2px}
.content{padding:26px 34px 30px}
h2{margin:0 0 12px;font-size:11px;letter-spacing:.16em;color:#66743a;text-transform:uppercase}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:26px}
.card{border:1px solid #b9cbb5;border-radius:14px;padding:14px 16px;background:#f3f8f0}
.card span{display:block;font-size:12px;color:#5b6a60}.card b{display:block;margin-top:4px;font-size:20px;color:#1c4630;font-variant-numeric:tabular-nums}
.wrap{overflow-x:auto;border-radius:12px}
table{width:100%;border-collapse:collapse;border:1px solid #b9cbb5;font-variant-numeric:tabular-nums}
th,td{border:1px solid #b9cbb5;padding:9px 12px;white-space:nowrap}
thead th{background:#1c4630;border-color:#1c4630;color:#fff;font-size:12.5px;letter-spacing:.03em;text-align:center}
tbody tr:nth-child(even) td{background:#f3f8f0}
.t-text{text-align:left}.t-money,.t-int{text-align:right}.t-datetime,.t-badge{text-align:center}
tfoot td{background:#dfe9dc;color:#1c4630;font-weight:700;border-top:2px solid #1c4630;border-bottom:2px solid #1c4630}
.badge{display:inline-block;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:700}
.empty{text-align:center;color:#5b6a60;font-style:italic;padding:22px}
.foot{padding:16px 34px;background:#f3f8f0;border-top:1px solid #d5dfd1;color:#5b6a60;font-size:12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
@media(max-width:640px){.hero{padding:20px;gap:14px}.hero img{width:64px;height:64px}.brand{font-size:24px}.content{padding:20px}.foot{padding:14px 20px}.hint{display:none}}
@page{size:A4 landscape;margin:10mm}
@media print{body{background:#fff}.no-print{display:none!important}.sheet{margin:0;box-shadow:none;border-radius:0;max-width:none}.wrap{overflow:visible}th,td{padding:6px 8px;font-size:11px;white-space:normal}tr{page-break-inside:avoid}thead{display:table-header-group}tfoot{display:table-row-group}}
CSS;

        return '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $e($this->title ?: 'Laporan') . ' — Clover</title>'
            . ($logo ? '<link rel="icon" href="' . $logo . '">' : '')
            . '<style>' . $css . '</style></head><body>' . $bar
            . '<div class="sheet"><header class="hero">' . ($logo ? '<img src="' . $logo . '" alt="Logo Clover">' : '')
            . '<div><div class="brand">CLOVER</div><div class="tag">BOTANICAL LIVING</div>'
            . '<div class="title">' . $e($this->title ?: 'Laporan') . '</div><div class="meta">' . $e($this->meta) . '</div></div></header>'
            . '<main class="content">'
            . ($cards ? '<h2>Ringkasan</h2><div class="cards">' . $cards . '</div>' : '')
            . '<h2>Rincian data</h2><div class="wrap"><table><thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody>' . $foot . '</table></div>'
            . '</main><footer class="foot"><span>Dibuat otomatis oleh Clover Dashboard · ' . $e(now()->format('d/m/Y H:i')) . '</span><span>Terima kasih telah bersama Clover 🌿</span></footer></div>'
            . '</body></html>';
    }

    /* ------------------------------------------------------------------ */
    /*  CSV cadangan                                                       */
    /* ------------------------------------------------------------------ */

    private function csvResponse(string $filename)
    {
        $cols = $this->columns;
        $rows = $this->data;

        return response()->streamDownload(function () use ($cols, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8
            fputcsv($out, array_map(fn ($c) => $c['label'], $cols));
            foreach ($rows as $row) {
                $line = [];
                foreach ($cols as $i => $c) {
                    $v = $row[$i] ?? '';
                    $line[] = $v instanceof DateTimeInterface ? $v->format('Y-m-d H:i') : $v;
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ------------------------------------------------------------------ */
    /*  Registry gaya                                                      */
    /* ------------------------------------------------------------------ */

    private function reg(array &$list, string $xml): int
    {
        $i = array_search($xml, $list, true);
        if ($i === false) {
            $list[] = $xml;
            $i = count($list) - 1;
        }

        return (int) $i;
    }

    private function box(string $color = self::LINE, string $style = 'thin'): array
    {
        $s = [$style, $color];

        return ['l' => $s, 'r' => $s, 't' => $s, 'b' => $s];
    }

    private function style(array $o = []): int
    {
        $o += [
            'bold' => false, 'italic' => false, 'size' => 11, 'color' => self::INK, 'font' => 'Calibri',
            'fill' => null, 'border' => null, 'h' => null, 'v' => 'center', 'wrap' => false,
            'indent' => 0, 'fmt' => null,
        ];

        $font = '<font>' . ($o['bold'] ? '<b/>' : '') . ($o['italic'] ? '<i/>' : '')
            . '<sz val="' . $o['size'] . '"/><color rgb="FF' . $o['color'] . '"/>'
            . '<name val="' . $o['font'] . '"/><family val="2"/></font>';
        $fontId = $this->reg($this->fonts, $font);

        $fillId = 0;
        if ($o['fill']) {
            $fillId = 2 + $this->reg($this->fills, '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $o['fill'] . '"/><bgColor indexed="64"/></patternFill></fill>');
        }

        $borderId = 0;
        if ($o['border']) {
            $b = '<border>';
            foreach (['l' => 'left', 'r' => 'right', 't' => 'top', 'b' => 'bottom'] as $k => $tag) {
                $b .= isset($o['border'][$k])
                    ? '<' . $tag . ' style="' . $o['border'][$k][0] . '"><color rgb="FF' . $o['border'][$k][1] . '"/></' . $tag . '>'
                    : '<' . $tag . '/>';
            }
            $b .= '<diagonal/></border>';
            $borderId = 1 + $this->reg($this->borders, $b);
        }

        $fmtId = 0;
        if ($o['fmt']) {
            $fmtId = 164 + $this->reg($this->numFmts, $o['fmt']);
        }

        $align = '<alignment' . ($o['h'] ? ' horizontal="' . $o['h'] . '"' : '') . ' vertical="' . $o['v'] . '"'
            . ($o['wrap'] ? ' wrapText="1"' : '') . ($o['indent'] ? ' indent="' . $o['indent'] . '"' : '') . '/>';

        $xf = '<xf numFmtId="' . $fmtId . '" fontId="' . ($fontId + 1) . '" fillId="' . $fillId . '" borderId="' . $borderId . '" xfId="0"'
            . ' applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $align . '</xf>';

        return $this->reg($this->xfs, $xf) + 1; // 0 = gaya bawaan
    }

    /* ------------------------------------------------------------------ */
    /*  Util                                                               */
    /* ------------------------------------------------------------------ */

    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + (($i - 1) % 26)) . $s;
        }

        return $s;
    }

    private static function esc(mixed $v): string
    {
        $s = (string) $v;
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function serial(DateTimeInterface $d): float
    {
        return ($d->getTimestamp() + $d->getOffset()) / 86400 + 25569;
    }

    /* ------------------------------------------------------------------ */
    /*  Pembangun sheet                                                    */
    /* ------------------------------------------------------------------ */

    private function build(): string
    {
        $this->fonts = [];
        $this->fills = [];
        $this->borders = [];
        $this->numFmts = [];
        $this->xfs = [];

        $nc = max(1, count($this->columns));
        $last = $nc - 1;
        $rows = [];      // [r => ['ht'=>x|null, 'cells'=>[c=>xml]]]
        $merges = [];

        $put = function (int $r, int $c, string $xml, ?float $ht = null) use (&$rows) {
            $rows[$r]['cells'][$c] = $xml;
            if ($ht !== null) {
                $rows[$r]['ht'] = $ht;
            }
        };
        $txt = fn (int $r, int $c, mixed $v, int $s) => '<c r="' . self::col($c) . $r . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">' . self::esc($v) . '</t></is></c>';
        $num = fn (int $r, int $c, float|int $v, int $s) => '<c r="' . self::col($c) . $r . '" s="' . $s . '"><v>' . $v . '</v></c>';
        $blank = fn (int $r, int $c, int $s) => '<c r="' . self::col($c) . $r . '" s="' . $s . '"/>';

        /* ---------- banner (baris 1-4) ---------- */
        $bn = ['fill' => self::FOREST];
        $bannerRows = [
            1 => [30, 'CLOVER', ['bold' => true, 'size' => 24, 'color' => 'FFFFFF', 'font' => 'Georgia']],
            2 => [18, 'BOTANICAL LIVING', ['size' => 9, 'color' => 'A3BBA0', 'bold' => true]],
            3 => [24, $this->title ?: 'Laporan', ['bold' => true, 'size' => 15, 'color' => '9ADBA0']],
            4 => [20, $this->meta, ['size' => 10, 'color' => 'DFE9DC']],
        ];
        foreach ($bannerRows as $r => [$ht, $text, $f]) {
            $sTxt = $this->style($bn + $f + ['h' => 'left', 'indent' => 14]);
            $sFill = $this->style($bn);
            $put($r, 0, $txt($r, 0, $text, $sTxt), $ht);
            for ($c = 1; $c <= $last; $c++) {
                $put($r, $c, $blank($r, $c, $sFill));
            }
            if ($last > 0) {
                $merges[] = 'A' . $r . ':' . self::col($last) . $r;
            }
        }

        $r = 5;
        $put($r, 0, '', 10);
        unset($rows[$r]['cells'][0]);
        $r++;

        /* ---------- ringkasan ---------- */
        $moneyFmt = '"Rp" #,##0;-"Rp" #,##0;"–"';
        $intFmt = '#,##0';
        $dtFmt = 'dd/mm/yyyy hh:mm';

        $secStyle = $this->style(['bold' => true, 'size' => 10, 'color' => '66743A']);
        if ($this->summary) {
            $put($r, 0, $txt($r, 0, 'RINGKASAN', $secStyle), 20);
            $r++;
            $lab = $this->style(['bold' => true, 'color' => self::FOREST, 'fill' => self::SAGE_SOFT, 'border' => $this->box(), 'h' => 'left', 'indent' => 1]);
            foreach ($this->summary as $item) {
                [$label, $value] = $item;
                $type = $item[2] ?? 'text';
                $val = match ($type) {
                    'money' => $this->style(['bold' => true, 'size' => 12, 'color' => self::FOREST, 'border' => $this->box(), 'fmt' => $moneyFmt, 'h' => 'left', 'indent' => 1]),
                    'int' => $this->style(['bold' => true, 'size' => 12, 'color' => self::FOREST, 'border' => $this->box(), 'fmt' => $intFmt, 'h' => 'left', 'indent' => 1]),
                    default => $this->style(['bold' => true, 'size' => 12, 'color' => self::FOREST, 'border' => $this->box(), 'h' => 'left', 'indent' => 1]),
                };
                $put($r, 0, $txt($r, 0, $label, $lab), 22);
                if ($type === 'text') {
                    $put($r, 1, $txt($r, 1, $value, $val));
                } else {
                    $put($r, 1, $num($r, 1, (float) $value, $val));
                }
                if ($nc >= 3) {
                    $put($r, 2, $blank($r, 2, $val));
                    $merges[] = 'B' . $r . ':C' . $r;
                }
                $r++;
            }
            $put($r, 0, '', 12);
            unset($rows[$r]['cells'][0]);
            $r++;
        }

        /* ---------- tabel ---------- */
        $put($r, 0, $txt($r, 0, 'RINCIAN DATA', $secStyle), 20);
        $r++;

        $hdr = $this->style([
            'bold' => true, 'color' => 'FFFFFF', 'fill' => self::FOREST, 'h' => 'center', 'wrap' => true,
            'border' => $this->box(self::FOREST),
        ]);
        $headerRow = $r;
        foreach ($this->columns as $c => $col) {
            $put($r, $c, $txt($r, $c, $col['label'], $hdr), $c === 0 ? 26 : null);
        }
        $r++;

        $firstData = $r;
        $sums = [];
        foreach ($this->data as $i => $row) {
            $zebra = $i % 2 === 1 ? self::ZEBRA : 'FFFFFF';
            foreach ($this->columns as $c => $col) {
                $type = $col['type'] ?? 'text';
                $v = $row[$c] ?? null;
                $base = ['fill' => $zebra, 'border' => $this->box(), 'color' => self::INK];

                if (isset($col['badge']) && $v !== null && $v !== '') {
                    $bd = $col['badge'][(string) $v] ?? ['fg' => self::INK, 'bg' => $zebra];
                    $s = $this->style(['bold' => true, 'size' => 10, 'color' => $bd['fg'], 'fill' => $bd['bg'], 'border' => $this->box(), 'h' => 'center']);
                    $put($r, $c, $txt($r, $c, $v, $s), $c === 0 ? 20 : null);
                    continue;
                }

                switch ($type) {
                    case 'money':
                        $s = $this->style($base + ['fmt' => $moneyFmt, 'h' => 'right', 'indent' => 1]);
                        $n = (float) ($v ?? 0);
                        $put($r, $c, $num($r, $c, $n, $s), $c === 0 ? 20 : null);
                        $sums[$c] = ($sums[$c] ?? 0) + $n;
                        break;
                    case 'int':
                        $s = $this->style($base + ['fmt' => $intFmt, 'h' => 'right', 'indent' => 1]);
                        $n = (float) ($v ?? 0);
                        $put($r, $c, $num($r, $c, $n, $s), $c === 0 ? 20 : null);
                        $sums[$c] = ($sums[$c] ?? 0) + $n;
                        break;
                    case 'datetime':
                        $s = $this->style($base + ['fmt' => $dtFmt, 'h' => 'center']);
                        if ($v instanceof DateTimeInterface) {
                            $put($r, $c, $num($r, $c, self::serial($v), $s), $c === 0 ? 20 : null);
                        } else {
                            $put($r, $c, $blank($r, $c, $s), $c === 0 ? 20 : null);
                        }
                        break;
                    default:
                        $s = $this->style($base + ['h' => 'left', 'indent' => 1]);
                        $put($r, $c, ($v === null || $v === '') ? $blank($r, $c, $s) : $txt($r, $c, $v, $s), $c === 0 ? 20 : null);
                }
            }
            $r++;
        }
        $lastData = $r - 1;

        if (! $this->data) {
            $s = $this->style(['italic' => true, 'color' => '5B6A60', 'h' => 'center', 'border' => $this->box()]);
            for ($c = 0; $c <= $last; $c++) {
                $put($r, $c, $c === 0 ? $txt($r, $c, 'Tidak ada data untuk ditampilkan.', $s) : $blank($r, $c, $s), $c === 0 ? 26 : null);
            }
            if ($last > 0) {
                $merges[] = 'A' . $r . ':' . self::col($last) . $r;
            }
            $r++;
        } elseif ($this->totals) {
            $edge = ['l' => ['thin', self::LINE], 'r' => ['thin', self::LINE], 't' => ['medium', self::FOREST], 'b' => ['medium', self::FOREST]];
            foreach ($this->columns as $c => $col) {
                $type = $col['type'] ?? 'text';
                $common = ['bold' => true, 'color' => self::FOREST, 'fill' => self::SAGE_SOFT, 'border' => $edge];
                if (! empty($col['sum']) && in_array($type, ['money', 'int'], true)) {
                    $s = $this->style($common + ['fmt' => $type === 'money' ? $moneyFmt : $intFmt, 'h' => 'right', 'indent' => 1]);
                    $L = self::col($c);
                    $put($r, $c, '<c r="' . $L . $r . '" s="' . $s . '"><f>SUM(' . $L . $firstData . ':' . $L . $lastData . ')</f><v>' . ($sums[$c] ?? 0) . '</v></c>', $c === 0 ? 24 : null);
                } elseif ($c === 0) {
                    $s = $this->style($common + ['h' => 'left', 'indent' => 1]);
                    $put($r, $c, $txt($r, $c, 'TOTAL', $s), 24);
                } else {
                    $put($r, $c, $blank($r, $c, $this->style($common)));
                }
            }
            $r++;
        }
        $tableEnd = $r - 1;

        /* ---------- footer ---------- */
        $put($r, 0, '', 12);
        unset($rows[$r]['cells'][0]);
        $r++;
        $ft = $this->style(['italic' => true, 'size' => 9, 'color' => '5B6A60', 'h' => 'left']);
        $put($r, 0, $txt($r, 0, 'Dibuat otomatis oleh Clover Dashboard · ' . now()->format('d/m/Y H:i') . ' · Terima kasih telah bersama Clover 🌿', $ft), 16);
        if ($last > 0) {
            $merges[] = 'A' . $r . ':' . self::col($last) . $r;
        }
        $lastRow = $r;

        /* ---------- XML sheet ---------- */
        $widths = '';
        foreach ($this->columns ?: [['width' => 20]] as $c => $col) {
            $w = (float) ($col['width'] ?? 18);
            if ($c === 0) {
                $w = max($w, 18); // ruang untuk logo
            }
            $widths .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        ksort($rows);
        $sd = '';
        foreach ($rows as $rn => $rw) {
            $cells = $rw['cells'] ?? [];
            ksort($cells);
            $sd .= '<row r="' . $rn . '"' . (isset($rw['ht']) ? ' ht="' . $rw['ht'] . '" customHeight="1"' : '') . '>' . implode('', $cells) . '</row>';
        }

        $mc = $merges ? '<mergeCells count="' . count($merges) . '">' . implode('', array_map(fn ($m) => '<mergeCell ref="' . $m . '"/>', $merges)) . '</mergeCells>' : '';
        $af = $this->data ? '<autoFilter ref="A' . $headerRow . ':' . self::col($last) . $lastData . '"/>' : '';

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><tabColor rgb="FF' . self::FOREST . '"/><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<dimension ref="A1:' . self::col($last) . $lastRow . '"/>'
            . '<sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18" customHeight="1"/>'
            . '<cols>' . $widths . '</cols>'
            . '<sheetData>' . $sd . '</sheetData>'
            . $af . $mc
            . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '<drawing r:id="rId1"/>'
            . '</worksheet>';

        /* ---------- styles.xml ---------- */
        $numFmtXml = '';
        if ($this->numFmts) {
            $numFmtXml = '<numFmts count="' . count($this->numFmts) . '">';
            foreach ($this->numFmts as $i => $code) {
                $numFmtXml .= '<numFmt numFmtId="' . (164 + $i) . '" formatCode="' . self::esc($code) . '"/>';
            }
            $numFmtXml .= '</numFmts>';
        }
        $defaultFont = '<font><sz val="11"/><color rgb="FF' . self::INK . '"/><name val="Calibri"/><family val="2"/></font>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $numFmtXml
            . '<fonts count="' . (count($this->fonts) + 1) . '">' . $defaultFont . implode('', $this->fonts) . '</fonts>'
            . '<fills count="' . (count($this->fills) + 2) . '"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>' . implode('', $this->fills) . '</fills>'
            . '<borders count="' . (count($this->borders) + 1) . '"><border><left/><right/><top/><bottom/><diagonal/></border>' . implode('', $this->borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . (count($this->xfs) + 1) . '"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' . implode('', $this->xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';

        /* ---------- file pendukung ---------- */
        $sn = str_replace("'", "''", $this->sheetName);
        $rangeFilter = "'" . $sn . "'!\$A\$" . $headerRow . ':$' . self::col($last) . '$' . max($lastData, $headerRow);
        $names = '<definedName name="_xlnm.Print_Titles" localSheetId="0">' . self::esc("'" . $sn . "'!\$" . $headerRow . ':$' . $headerRow) . '</definedName>';
        if ($this->data) {
            $names = '<definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">' . self::esc($rangeFilter) . '</definedName>' . $names;
        }

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="12000"/></bookViews>'
            . '<sheets><sheet name="' . self::esc($this->sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames>' . $names . '</definedNames>'
            . '<calcPr calcId="191029" fullCalcOnLoad="1"/>'
            . '</workbook>';

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Default Extension="png" ContentType="image/png"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $shRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            . '</Relationships>';

        $dwRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.png"/>'
            . '</Relationships>';

        $px = 9525; // 1 px dalam EMU
        $size = 100 * $px;
        $drawing = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            . '<xdr:oneCellAnchor>'
            . '<xdr:from><xdr:col>0</xdr:col><xdr:colOff>' . (6 * $px) . '</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>' . (11 * $px) . '</xdr:rowOff></xdr:from>'
            . '<xdr:ext cx="' . $size . '" cy="' . $size . '"/>'
            . '<xdr:pic>'
            . '<xdr:nvPicPr><xdr:cNvPr id="2" name="Logo Clover" descr="Logo Clover"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
            . '<xdr:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            . '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $size . '" cy="' . $size . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
            . '</xdr:pic><xdr:clientData/>'
            . '</xdr:oneCellAnchor></xdr:wsDr>';

        $logoPath = resource_path('export/clover-logo.png');
        $logo = is_file($logoPath) ? (string) file_get_contents($logoPath) : '';

        $tmp = tempnam(sys_get_temp_dir(), 'clv');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            @unlink($tmp);
            throw new \RuntimeException('Tidak dapat membuat file ekspor.');
        }
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $shRels);
        $zip->addFromString('xl/drawings/drawing1.xml', $drawing);
        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $dwRels);
        $zip->addFromString('xl/media/image1.png', $logo);
        $zip->close();

        $bin = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bin;
    }
}
