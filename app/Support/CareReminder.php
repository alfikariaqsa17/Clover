<?php

namespace App\Support;

use App\Models\CareSchedule;
use App\Models\User;
use App\Models\UserNotification;

class CareReminder
{
    /** Buat notifikasi untuk jadwal yang jatuh tempo/lewat (maksimal satu per jadwal per hari). */
    public static function generate(User $user): int
    {
        $due = CareSchedule::with('userPlant')
            ->where('is_active', true)
            ->whereDate('next_due_date', '<=', today()->toDateString())
            ->whereHas('userPlant', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        $made = 0;
        foreach ($due as $s) {
            $title = ucfirst($s->type_label) . ' ' . $s->userPlant->nickname;
            $exists = UserNotification::where('user_id', $user->id)
                ->where('type', 'care')->where('title', $title)
                ->whereDate('created_at', today()->toDateString())->exists();
            if (! $exists) {
                Notify::to($user->id, 'care', $title, 'Jadwal perawatan jatuh tempo pada ' . $s->next_due_date->format('d-m-Y') . '.', route('account.calendar'));
                $made++;
            }
        }

        return $made;
    }
}
