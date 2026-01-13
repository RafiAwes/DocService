<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ServiceQuoteRequestToCustomer extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $service;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
        $this->service = $quote->serviceQuote?->service;
        $this->user = $quote->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quote Request Confirmation - #' . $this->quote->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.service-quote-customer',
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
