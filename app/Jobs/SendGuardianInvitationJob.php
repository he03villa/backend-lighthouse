<?php

namespace App\Jobs;

use App\Mail\GuardianInvitationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGuardianInvitationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public string $email,
        public string $participantName,
        public string $tenantName,
        public string $token,
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new GuardianInvitationMail(
            $this->token,
            $this->participantName,
            $this->tenantName,
        ));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send guardian invitation email', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
