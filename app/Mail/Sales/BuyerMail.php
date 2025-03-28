<?php

namespace App\Mail\Sales;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BuyerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $supportEmail;
    public $paymentCode;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email, $supportEmail, $paymentCode)
    {
        $this->name = $name;
        $this->email = $email;
        $this->supportEmail = $supportEmail;
        $this->paymentCode = $paymentCode;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@bullspay.com.br', 'Bulls Pay'),
            subject: 'Compra concluída com sucesso!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.buyer-payment',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'supportEmail' => $this->supportEmail,
                'paymentCode' => $this->paymentCode,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
