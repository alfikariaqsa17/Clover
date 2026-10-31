<?php

namespace App\Http\Controllers;

use App\Models\CareLog;
use App\Models\CareSchedule;
use App\Models\Plant;
use App\Models\UserPlant;
use App\Support\Msg;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserPlantController extends Controller
{
    private function mine(Request $r, UserPlant $p): UserPlant
    {
        abort_unless((int) $p->user_id === (int) $r->user()->id, 404);

        return $p;
    }

    private function rules(): array
    {
        return [
            'nickname' => 'required|string|min:2|max:80',
            'plant_id' => 'nullable|integer|exists:plants,id',
            'location' => 'nullable|string|max:80',
            'acquired_at' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ];
    }

    public function index(Request $r)
    {
        return view('account.plants.index', [
            'plants' => $r->user()->plants()->with(['plant', 'schedules'])->latest()->get(),
            'catalog' => Plant::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules(), Msg::messages(), Msg::attributes());
        unset($data['photo']);
        if ($r->hasFile('photo')) {
            $data['photo'] = Upload::save($r->file('photo'), 'my-plants');
        }

        $up = $r->user()->plants()->create($data);

        if (! empty($data['plant_id']) && ($plant = Plant::find($data['plant_id']))) {
            $up->schedules()->create([
                'type' => 'siram', 'every_days' => max(1, (int) $plant->water_days),
                'next_due_date' => today()->addDays(max(1, (int) $plant->water_days)), 'is_active' => true,
            ]);
        }

        return redirect()->route('account.plants.show', $up)->with('toast', 'Tanaman ditambahkan ke koleksi Anda.');
    }

    public function show(Request $r, UserPlant $userPlant)
    {
        $this->mine($r, $userPlant);
        $userPlant->load(['plant', 'schedules', 'logs']);

        return view('account.plants.show', ['up' => $userPlant, 'catalog' => Plant::orderBy('name')->get(['id', 'name'])]);
    }

    public function update(Request $r, UserPlant $userPlant)
    {
        $this->mine($r, $userPlant);
        $data = $r->validate($this->rules(), Msg::messages(), Msg::attributes());
        unset($data['photo']);
        if ($r->hasFile('photo')) {
            Upload::delete($userPlant->photo, 'my-plants');
            $data['photo'] = Upload::save($r->file('photo'), 'my-plants');
        }
        $userPlant->update($data);

        return back()->with('toast', 'Data tanaman diperbarui.');
    }

    public function destroy(Request $r, UserPlant $userPlant)
    {
        $this->mine($r, $userPlant);
        Upload::delete($userPlant->photo, 'my-plants');
        $userPlant->delete();

        return redirect()->route('account.plants')->with('toast', 'Tanaman dihapus dari koleksi.');
    }

    public function scheduleStore(Request $r, UserPlant $userPlant)
    {
        $this->mine($r, $userPlant);
        $data = $r->validate([
            'type' => ['required', Rule::in(array_keys(CareSchedule::TYPES))],
            'every_days' => 'required|integer|min:1|max:365',
            'next_due_date' => 'required|date|after_or_equal:today',
        ], Msg::messages(), Msg::attributes());
        $userPlant->schedules()->create($data + ['is_active' => true]);

        return back()->with('toast', 'Jadwal perawatan ditambahkan.');
    }

    private function mySchedule(Request $r, CareSchedule $s): CareSchedule
    {
        $s->load('userPlant');
        abort_unless($s->userPlant && (int) $s->userPlant->user_id === (int) $r->user()->id, 404);

        return $s;
    }

    public function scheduleDone(Request $r, CareSchedule $schedule)
    {
        $this->mySchedule($r, $schedule);
        CareLog::create([
            'user_plant_id' => $schedule->user_plant_id, 'care_schedule_id' => $schedule->id,
            'type' => $schedule->type, 'note' => 'Selesai sesuai jadwal', 'done_at' => today(),
        ]);
        $schedule->update(['last_done_at' => today(), 'next_due_date' => today()->addDays($schedule->every_days)]);

        return back()->with('toast', ucfirst($schedule->type_label) . ' ditandai selesai. Jadwal berikutnya ' . $schedule->next_due_date->format('d-m-Y') . '.');
    }

    public function scheduleDestroy(Request $r, CareSchedule $schedule)
    {
        $this->mySchedule($r, $schedule)->delete();

        return back()->with('toast', 'Jadwal dihapus.');
    }

    public function logStore(Request $r, UserPlant $userPlant)
    {
        $this->mine($r, $userPlant);
        $data = $r->validate([
            'type' => ['required', Rule::in(array_keys(CareSchedule::TYPES))],
            'note' => 'nullable|string|max:500',
            'done_at' => 'required|date|before_or_equal:today',
        ], Msg::messages(), Msg::attributes());
        $userPlant->logs()->create($data);

        return back()->with('toast', 'Catatan perawatan disimpan.');
    }
}
