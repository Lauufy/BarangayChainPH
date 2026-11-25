<?php

namespace App\Notifications;

use App\Models\PermitRevision;
use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class PermitCorrectionsRequested extends Notification implements ShouldQueue
{
    use Queueable;

    private ?string $resubmitUrl = null;

    public function __construct(public ServiceRequest $application, public PermitRevision $revision)
    {
        $this->resubmitUrl = $this->url();
    }

    public function via(object $notifiable): array
    {
        return filled($notifiable->email) ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'status' => ServiceRequest::RESUBMISSION_REQUIRED,
            'title' => 'Resubmission required',
            'message' => "Please correct the flagged items on request {$this->application->reference_number}.",
            'url' => $this->url(),
            'revision_id' => $this->revision->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Corrections requested for '.$this->application->reference_number)
            ->line("Please update these items on request {$this->application->reference_number}.");

        foreach ($this->revision->items as $item) {
            $mail->line($item['label'].' — '.$item['note']);
        }

        if ($this->revision->message) {
            $mail->line($this->revision->message);
        }

        return $mail->action('Resubmit application', $this->url());
    }

    private function url(): string
    {
        // Jobs queued before the link was captured still need to resolve it on retry.
        return $this->resubmitUrl ?? route('citizen.resubmissions.edit', [$this->application, 'revision' => $this->revision->id]);
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
