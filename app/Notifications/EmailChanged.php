<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected User $user,
        protected string $oldEmail
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Je e-mailadres is gewijzigd')
            ->greeting('Hallo '.$this->user->name.',')
            ->line('Je account e-mailadres is zojuist gewijzigd.')
            ->line('Oud e-mailadres: '.$this->oldEmail)
            ->line('Nieuw e-mailadres: '.$this->user->email)
            ->line('Heb jij dit niet gedaan? Neem dan direct contact op via de supportpagina.');
    }
}
