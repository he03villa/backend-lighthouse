<?php

namespace App\Listeners;

use App\Events\ProgramPublished;
use App\Models\User;
use App\Notifications\NewActivities;

class NotifyGuardiansOnProgramPublished
{
    public function handle(ProgramPublished $event): void
    {
        $guardians = $event->program->enrollments()
            ->with('participant.guardians')
            ->get()
            ->flatMap(fn ($enrollment) => $enrollment->participant?->guardians ?? collect())
            ->unique('id')
            ->values();

        $activitiesCount = $event->program->activities()->count();
        $url = url('/programs/'.$event->program->id);

        $guardians->each(fn (User $user) => $user->notify(new NewActivities($event->program->name, $activitiesCount, $url)));
    }
}
