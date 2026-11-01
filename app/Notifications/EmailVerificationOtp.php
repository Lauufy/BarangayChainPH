<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtp extends Notification
{
    use Queueable;

    public function __construct(
        protected string $otp,
        protected string $name
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('BarangayChain PH - Email Verification Code')
            ->greeting("Hello {$this->name}!")
            ->line('Thank you for registering with BarangayChain PH.')
            ->line('Your email verification code is:')
            ->line($this->otp)
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not create this account, you can ignore this email.');
    }
}
