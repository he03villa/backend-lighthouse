<?php

namespace App\Listeners;

use App\Events\EnrollmentCreated;
use App\Models\User;
use App\Notifications\NewActivities;

class NotifyGuardiansOnEnrollmentCreated
{
    public function handle(EnrollmentCreated $event): void
    {
        $enrollment = $event->enrollment->load('participant.guardians', 'program.activities');
        $guardians = $enrollment->participant?->guardians ?? collect();
        $activitiesCount = $enrollment->program->activities->count();
        $url = url('/programs/'.$enrollment->program->id);

        $guardians->each(fn (User $user) => $user->notify(new NewActivities($enrollment->program->name, $activitiesCount, $url)));
    }
}
