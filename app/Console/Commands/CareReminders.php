<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\CareReminder;
use Illuminate\Console\Command;

class CareReminders extends Command
{
    protected $signature = 'clover:care-reminders';

    protected $description = 'Buat notifikasi pengingat perawatan tanaman yang jatuh tempo';

    public function handle(): int
    {
        $total = 0;
        User::whereHas('plants.schedules', fn ($q) => $q->where('is_active', true)->whereDate('next_due_date', '<=', today()->toDateString()))
            ->each(function (User $u) use (&$total) {
                $total += CareReminder::generate($u);
            });

        $this->info("Notifikasi dibuat: {$total}");

        return self::SUCCESS;
    }
}
