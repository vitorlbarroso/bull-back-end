<?php

namespace App\Mail\Sales;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;


class GeneratePixMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $pixCode;
    public $support;
    public $price;
    public $id;

    public function __construct($name, $pixCode, $support, $price, $id)
    {
        $this->name = $name;
        $this->pixCode = $pixCode;
        $this->support = $support;
        $this->price = $price;
        $this->id = $id;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@bullspay.com.br', 'Bulls Pay'),
            subject: 'Pague o seu PIX aqui!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'mails.generate-pix',
            with: [
                'name' => $this->name,
                'pixCode' => $this->pixCode,
                'support' => $this->support,
                'price' => $this->price,
                'id' => $this->id,
            ],
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
