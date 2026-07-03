<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Installation;

class InstallationReminder extends Notification
{
    use Queueable;

    public function __construct(public Installation $installation) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Installation Reminder')
            ->line('You have an upcoming installation: '.$this->installation->title)
            ->line('Due date: '.optional($this->installation->due_date)->format('Y-m-d'))
            ->action('Open', url(route('engineer.installations.show', $this->installation)))
            ->line('This is an automated reminder.');
    }
}


