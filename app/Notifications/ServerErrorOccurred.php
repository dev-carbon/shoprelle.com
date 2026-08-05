<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Tells the administrators, by email, that the application just failed.
 *
 * Deliberately NOT queued: when the application is broken the queue may well
 * be broken too, and an alert waiting on a dead worker alerts nobody. Sending
 * is synchronous, and rate-limited where the notification is triggered
 * (bootstrap/app.php) so a crash loop cannot flood the inbox.
 *
 * The exception is flattened in the constructor rather than carried whole:
 * only what helps understand the failure is sent, the full trace stays in the
 * log the back-office journal reads.
 */
class ServerErrorOccurred extends Notification
{
    public string $exceptionClass;

    public string $message;

    public string $location;

    public ?string $url;

    public function __construct(Throwable $exception, ?string $url = null)
    {
        $this->exceptionClass = $exception::class;
        $this->message = $exception->getMessage() !== '' ? $exception->getMessage() : '(sans message)';
        $this->location = $exception->getFile().':'.$exception->getLine();
        $this->url = $url;
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('[Shoprelle] Erreur serveur — '.class_basename($this->exceptionClass))
            ->greeting('Une erreur vient de se produire sur le site.')
            ->line('**Erreur** : '.$this->exceptionClass)
            ->line('**Message** : '.$this->message)
            ->line('**Où** : '.$this->location)
            ->lineIf($this->url !== null, '**Page** : '.$this->url)
            ->line('Le détail complet est dans le journal du back-office.');
    }
}
