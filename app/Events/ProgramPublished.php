<?php

namespace App\Events;

use App\Models\Program;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgramPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Program $program) {}
}
