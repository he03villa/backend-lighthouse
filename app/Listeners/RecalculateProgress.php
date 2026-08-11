<?php

namespace App\Listeners;

use App\Events\SubmissionReviewed;
use App\Services\ProgressService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecalculateProgress implements ShouldQueue
{
    public function __construct(protected ProgressService $service)
    {
    }

    public function handle(SubmissionReviewed $event): void
    {
        $this->service->recalculate($event->enrollment);
    }
}
