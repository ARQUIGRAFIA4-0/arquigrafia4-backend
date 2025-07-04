<?php

namespace App\Notifications;

use App\Models\AccountVerificationToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountVerificationRequested extends Notification
{
    use Queueable;

    protected AccountVerificationToken $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(AccountVerificationToken $token)
    {
        $this->token = $token;
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
            ->from(env('MAIL_FROM_ADDRESS', 'arquigrafia@usp.br'), env('APP_NAME', 'ARQUIGRAFIA'))
            ->subject('Verificação de conta')
            ->greeting('Olá!')
            ->line('Seu código de validação da conta é o seguinte:')
            ->line($this->token->token)
            ->salutation('Obrigado, ' . env('APP_NAME', 'ARQUIGRAFIA'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
