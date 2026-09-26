<?php

namespace App\Notifications;

use App\Models\WorkVisaApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkVisaApplicationStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public WorkVisaApplication $application,
        public ?string $note = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Work visa application '.$this->application->reference.': '.$this->application->status->label())
            ->greeting('Hello '.$this->application->first_name.',')
            ->line('The status of your work visa application '.$this->application->reference.' is now: '.$this->application->status->label().'.');

        if ($this->note !== null) {
            $message->line('Note from our team: '.$this->note);
        }

        return $message->action('View your application', route('work-visa.applications.show', $this->application));
    }
}
