<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class NewQuoteRequest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $quote;
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }
  
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $is_customer  = $notifiable->id === $this->quote->user_id;
        $url = url('/quotes/'.$this->quote->id);
        if ($is_customer){
            return (new MailMessage)
            ->subject('Quote Request Received - #' . $this->quote->id)
            ->greeting("Hello {$notifiable->name},")
            ->line('Thank you for requesting a quote! We have received your request.')
            ->action('View Quote', $url)
            ->line('We will get back to you shortly.');
        }
        return (new MailMessage)
            ->subject('New Quote Request - #' . $this->quote->id)
            ->greeting("Hi Admin,")
            ->line("A new quote request has been submitted by {$this->quote->user->name}.")
            ->line('Quote Details:')
            ->line('Quote ID: ' . $this->quote->id)
            ->action('Review Quote Request', $url)
            ->line('Please review the quote request at your earliest convenience.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {

        $is_customer  = $notifiable->id === $this->quote->user_id;
        if ($is_customer){
            return [
                'title'     => 'Quote Request Sent Successfully',
                'message'   => 'Your quote #'.$this->quote->slug.' request has been received.',
                'quote_id'  => $this->quote->id,
                // 'amount'    => $this->quote->total_amount,
                'type'      => 'quote_requested', // Helps frontend decide which icon to show
            ];
        };
        return [
            'title'     => 'New Quote Requested',
            'message'   => 'Quote #'.$this->quote->slug.' was requested by '.$this->quote->user->name,
            'quote_id'  => $this->quote->id,
            // 'amount'    => $this->quote->total_amount,
            'type'      => 'quote_requested', // Helps frontend decide which icon to show
        ];
    }
}
