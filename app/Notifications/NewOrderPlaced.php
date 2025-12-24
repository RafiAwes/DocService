<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderPlaced extends Notification
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('A new order has been placed.')
            ->line('Order ID: '.$this->order->id)
            ->line('Total: $'.number_format($this->order->total, 2))
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'     => 'New Order Received',
            'message'   => 'Order #'.$this->order->slug.' was placed by '.$this->order->user->name,
            'order_id'  => $this->order->id,
            'amount'    => $this->order->total_amount,
            'type'      => 'order_placed', // Helps frontend decide which icon to show
        ];
    }
}
