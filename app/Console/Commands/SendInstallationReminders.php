<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Installation;
use App\Notifications\InstallationReminder;

class SendInstallationReminders extends Command
{
    protected $signature = 'installations:send-reminders';
    protected $description = 'Send reminder notifications for installations due tomorrow';

    public function handle(): int
    {
        $due = now()->addDay()->toDateString();
        $installations = Installation::with('assignee')
            ->whereDate('due_date', $due)
            ->get();

        foreach ($installations as $i) {
            if ($i->assignee) {
                $i->assignee->notify(new InstallationReminder($i));
            }
        }

        $this->info('Reminders sent for '.$installations->count().' installations.');
        return self::SUCCESS;
    }
}


