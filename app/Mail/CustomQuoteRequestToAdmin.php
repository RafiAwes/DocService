<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomQuoteRequestToAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $customQuote;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
        $this->customQuote = $quote->customQuote;
        $this->user = $quote->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Custom Document Quote Request - #' . $this->quote->id,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.custom-quote-admin',
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
