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
        $is_customer = $notifiable->id === $this->quote->user_id;
        $url = url('/quotes/' . $this->quote->id);

        // Load service details if it's a service quote
        $serviceDetails = null;
        if ($this->quote->type === 'service' && $this->quote->serviceQuote) {
            $serviceDetails = $this->quote->serviceQuote->service;
        }

        if ($is_customer) {
            // Email to customer
            $mail = (new MailMessage)
                ->subject('Quote Request Confirmation - #' . $this->quote->id)
            ->greeting("Hello {$notifiable->name},")
                ->line('Thank you for requesting a quote from DocAssist!')
                ->line('We have successfully received your quote request and our team will review it shortly.');

            if ($serviceDetails) {
                $mail->line('')
                    ->line('**Service Requested:**')
                    ->line('**' . $serviceDetails->title . '**')
                    ->line($serviceDetails->short_description ?? '');

                if ($serviceDetails->price) {
                    $mail->line('Base Price: $' . number_format($serviceDetails->price, 2));
                }
            }

            $mail->line('')
                ->line('**Quote Details:**')
                ->line('Quote ID: #' . $this->quote->id)
                ->line('Request Date: ' . $this->quote->created_at->format('F d, Y h:i A'))
                ->line('')
                ->action('View Quote Details', $url)
                ->line('Our team will review your requirements and get back to you with a detailed quote within 24-48 hours.')
                ->line('If you have any questions, please don\'t hesitate to contact us.')
                ->line('')
                ->line('Best regards,')
                ->line('DocAssist Team');

            return $mail;
        }

        // Email to admin
        $mail = (new MailMessage)
            ->subject('New Quote Request Received - #' . $this->quote->id)
            ->greeting("Hi Admin,")
            ->line('A new quote request has been submitted and requires your attention.');

        if ($serviceDetails) {
            $mail->line('')
                ->line('**Service Details:**')
                ->line('Service: **' . $serviceDetails->title . '**')
                ->line('Category: ' . ($serviceDetails->category->name ?? 'N/A'));

            if ($serviceDetails->price) {
                $mail->line('Base Price: $' . number_format($serviceDetails->price, 2));
            }
        }

        $mail->line('')
            ->line('**Customer Information:**')
            ->line('Name: ' . $this->quote->user->name)
            ->line('Email: ' . $this->quote->user->email)
            ->line('')
            ->line('**Quote Information:**')
            ->line('Quote ID: #' . $this->quote->id)
            ->line('Type: ' . ucfirst($this->quote->type))
            ->line('Submitted: ' . $this->quote->created_at->format('F d, Y h:i A'))
            ->line('')
            ->action('Review Quote Request', $url)
            ->line('Please review and respond to this quote request as soon as possible.');

        return $mail;
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
