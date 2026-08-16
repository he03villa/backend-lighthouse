<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewActivities extends Notification
{
    public function __construct(
        public string $programName,
        public int $activitiesCount,
        public string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevas actividades en '.$this->programName)
            ->greeting('Hola!')
            ->line('Se publicaron nuevas actividades en el programa "'.$this->programName.'".')
            ->line('Actividades disponibles: '.$this->activitiesCount)
            ->action('Ver actividades', $this->url);
    }
}
