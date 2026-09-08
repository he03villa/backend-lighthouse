<?php

namespace App\Providers;

use App\Events\ConversationCreated;
use App\Events\ConversationUpdated;
use App\Events\EnrollmentCreated;
use App\Events\MessageSent;
use App\Events\ProgramPublished;
use App\Events\SubmissionReviewed;
use App\Listeners\NotifyConversationParticipants;
use App\Listeners\NotifyGuardiansOnEnrollmentCreated;
use App\Listeners\NotifyGuardiansOnProgramPublished;
use App\Listeners\RecalculateProgress;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SubmissionReviewed::class => [
            RecalculateProgress::class,
        ],
        ProgramPublished::class => [
            NotifyGuardiansOnProgramPublished::class,
        ],
        EnrollmentCreated::class => [
            NotifyGuardiansOnEnrollmentCreated::class,
        ],
        MessageSent::class => [
            NotifyConversationParticipants::class,
        ],
        ConversationCreated::class => [
            NotifyConversationParticipants::class,
        ],
        ConversationUpdated::class => [
            NotifyConversationParticipants::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
