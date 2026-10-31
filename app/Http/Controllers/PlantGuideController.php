<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Models\PlantProblem;
use Illuminate\Http\Request;

class PlantGuideController extends Controller
{
    public function index(Request $r)
    {
        $q = Plant::query();
        $s = trim((string) $r->query('q', ''));
        if ($s !== '') {
            $q->where(fn ($w) => $w->where('name', 'like', '%' . $s . '%')->orWhere('latin_name', 'like', '%' . $s . '%'));
        }
        if (in_array($r->query('cahaya'), ['rendah', 'sedang', 'tinggi'], true)) {
            $q->where('light_level', $r->query('cahaya'));
        }
        if (in_array($r->query('perawatan'), ['mudah', 'sedang', 'sulit'], true)) {
            $q->where('care_level', $r->query('perawatan'));
        }

        return view('guide.index', ['plants' => $q->orderBy('name')->paginate(12)->withQueryString()]);
    }

    public function show(Plant $plant)
    {
        $plant->load(['products' => fn ($q) => $q->where('is_active', true)->with('images')]);

        return view('guide.show', ['plant' => $plant, 'problems' => PlantProblem::where('is_active', true)->orderBy('sort_order')->take(4)->get()]);
    }

    public function finder(Request $r)
    {
        $results = null;
        $input = $r->only(['cahaya', 'pengalaman', 'ruang', 'siram', 'hewan']);

        if ($r->has('cahaya')) {
            $r->validate([
                'cahaya' => 'required|in:rendah,sedang,tinggi',
                'pengalaman' => 'required|in:pemula,menengah,mahir',
                'ruang' => 'nullable|string|max:30',
                'siram' => 'nullable|in:jarang,rutin',
            ]);

            $levels = ['rendah' => 0, 'sedang' => 1, 'tinggi' => 2];
            $allowed = ['pemula' => ['mudah'], 'menengah' => ['mudah', 'sedang'], 'mahir' => ['mudah', 'sedang', 'sulit']][$r->input('pengalaman')];
            $wantLight = $levels[$r->input('cahaya')];

            $results = Plant::with(['products' => fn ($q) => $q->where('is_active', true)->with('images')])->get()
                ->map(function (Plant $p) use ($levels, $allowed, $wantLight, $r) {
                    $diff = abs(($levels[$p->light_level] ?? 1) - $wantLight);
                    if ($diff > 1 || ! in_array($p->care_level, $allowed, true)) {
                        return null;
                    }
                    if ($r->boolean('hewan') && $p->pet_safe !== true) {
                        return null;
                    }
                    $score = $diff === 0 ? 3 : 1;
                    $why = [$diff === 0 ? 'Cocok dengan intensitas cahaya ruangan Anda' : 'Masih toleran pada cahaya ruangan Anda'];
                    if ($r->filled('ruang') && in_array($r->input('ruang'), $p->placement_list, true)) {
                        $score += 2;
                        $why[] = 'Cocok untuk ' . $r->input('ruang');
                    }
                    if ($r->input('siram') === 'jarang' && $p->water_days >= 10) {
                        $score++;
                        $why[] = 'Jarang perlu disiram';
                    }
                    if ($r->input('siram') === 'rutin' && $p->water_days <= 4) {
                        $score++;
                        $why[] = 'Sesuai jadwal siram rutin';
                    }
                    if ($r->boolean('hewan')) {
                        $why[] = 'Aman untuk hewan peliharaan';
                    }
                    $p->score = $score;
                    $p->why = $why;

                    return $p;
                })->filter()->sortByDesc('score')->values()->take(12);
        }

        return view('guide.finder', compact('results', 'input'));
    }

    public function health()
    {
        return view('guide.health', ['problems' => PlantProblem::where('is_active', true)->orderBy('sort_order')->get()]);
    }
}
