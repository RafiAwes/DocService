<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewOrderPlaced extends Notification implements ShouldQueue
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $is_customer  = $notifiable->id === $this->order->user_id;
        $url = url('/orders/'.$this->order->id);
        if ($is_customer){
            return (new MailMessage)

            ->subject('Order Confirmation - #' . $this->order->id)
            ->greeting("Hello {$notifiable->name},")
            ->line('Thank you for your purchase! We have received your order.')
            ->action('View Order', $url)
            ->line('We will notify you once processing begins.');
        }

        return (new MailMessage)
           ->subject('New Order Alert - #' . $this->order->id)
            ->greeting('Hello Admin,')
            ->line("A new order has been placed by {$this->order->user->name}.")
            ->line("Total Amount: {$this->order->grand_total}")
            ->action('Manage Order', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {

        $is_customer  = $notifiable->id === $this->order->user_id;
        if ($is_customer){
            return [
                'title'     => 'Order Placed Successfully',
                'message'   => 'Your order #'.$this->order->slug.' has been placed successfully.',
                'order_id'  => $this->order->id,
                'amount'    => $this->order->total_amount,
                'type'      => 'customer_order_placed', // Helps frontend decide which icon to show
            ];
        }
        return [
            'title'     => 'New Order Received',
            'message'   => 'Order #'.$this->order->slug.' was placed by '.$this->order->user->name,
            'order_id'  => $this->order->id,
            'amount'    => $this->order->total_amount,
            'type'      => 'order_placed', // Helps frontend decide which icon to show
        ];
    }
}
