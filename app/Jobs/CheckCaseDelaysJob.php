<?php

namespace App\Jobs;

use App\Models\CaseModel;
use App\Models\User;
use App\Notifications\CaseDelayNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckCaseDelaysJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        $cases = CaseModel::with('priority')
            ->where('status', 'opened')
            ->get();

        if ($cases->isEmpty()) {
            return;
        }

        $users = User::whereHas('role.permissions', function ($q) {
            $q->where('permission_name', 'receive_notification');
        })->get();

        foreach ($cases as $case) {

            $createdAt = $case->created_at;
            $delayDays = $case->priority->delay_time;

            $deadline = $createdAt->copy()->addDays($delayDays);
            $warningTime = $deadline->copy()->subDay(); 

            foreach ($users as $user) {

    
                if ($now->between($warningTime, $deadline)) {
                    $user->notify(
                        new CaseDelayNotification($case, 'warning')
                    );
                }

    
                if ($now->greaterThan($deadline)) {
                    $user->notify(
                        new CaseDelayNotification($case, 'exceeded')
                    );
                }
            }
        }
    }
}

