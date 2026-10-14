<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ServiceRequest $serviceRequest) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Application rejected',
            'message' => "Your application {$this->serviceRequest->reference_number} was rejected.",
            'reason' => $this->serviceRequest->rejection_reason,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('BarangayChain PH - Application Rejected')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($data['message'])
            ->line('Reason: '.$data['reason'])
            ->line('Please review the reason and submit a new application if appropriate.')
            ->action('View My Applications', route('citizen.applications'));
    }
}
