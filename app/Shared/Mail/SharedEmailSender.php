<?php

namespace App\Shared\Mail;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class SharedEmailSender
{
    public function sendTenantEmail(
        string $recipient,
        string $subject,
        string $body,
        ?string $tenantName,
        ?string $tenantReplyTo,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
    ): void {
        $this->send(
            $recipient,
            $subject,
            $body,
            $this->displayName($tenantName),
            $this->replyToAddress($tenantReplyTo),
            $actionLabel,
            $actionUrl,
        );
    }

    public function sendAdminEmail(
        string $recipient,
        string $subject,
        string $body,
        ?string $supportEmail,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
    ): void {
        $this->send(
            $recipient,
            $subject,
            $body,
            $this->displayName(),
            $this->replyToAddress($supportEmail),
            $actionLabel,
            $actionUrl,
        );
    }

    public function replyToAddress(?string $address): string
    {
        $address = trim((string) $address);

        return filter_var($address, FILTER_VALIDATE_EMAIL)
            ? $address
            : $this->fromAddress();
    }

    private function send(
        string $recipient,
        string $subject,
        string $body,
        string $fromName,
        string $replyTo,
        ?string $actionLabel,
        ?string $actionUrl,
    ): void {
        $fromAddress = $this->fromAddress();

        Mail::send(
            ['html' => 'emails.notification', 'text' => 'emails.notification-text'],
            compact('subject', 'body', 'actionLabel', 'actionUrl'),
            function (Message $message) use ($recipient, $subject, $fromAddress, $fromName, $replyTo): void {
                $message
                    ->from($fromAddress, $fromName)
                    ->replyTo($replyTo)
                    ->to($recipient)
                    ->subject($subject);
            },
        );
    }

    private function fromAddress(): string
    {
        return trim((string) config('mail.from.address'));
    }

    private function displayName(?string $name = null): string
    {
        $name = trim((string) $name);

        if ($name !== '') {
            return $name;
        }

        return trim((string) config('mail.from.name'))
            ?: trim((string) config('app.name', 'Onlyvo'));
    }
}
