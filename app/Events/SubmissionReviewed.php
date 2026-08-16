<?php

namespace App\Events;

use App\Models\Enrollment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubmissionReviewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Enrollment $enrollment) {}
}
