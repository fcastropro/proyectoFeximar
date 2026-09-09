<?php

namespace App\Notifications;

use App\Models\Buyer;
use App\Models\Farm;
use App\Models\Order;
use App\Models\OrderFarmFulfillment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly OrderFarmFulfillment $fulfillment,
        public readonly Farm $farm,
        public readonly Buyer $buyer,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contactName = $this->buyer->contact_name
            ?: $this->buyer->company_name
            ?: 'cliente';

        $orderDate = $this->order->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i')
            ?? now()->format('Y-m-d H:i');

        $total = number_format((float) $this->order->total, 2, '.', ',');

        return (new MailMessage)
            ->subject('Tu pedido está en preparación - FEXIMAR')
            ->greeting("Hola {$contactName},")
            ->line("Tu pedido #{$this->order->id} ha sido aceptado por la finca y ya se encuentra en preparación.")
            ->line('Resumen:')
            ->line("- Pedido: #{$this->order->id}")
            ->line("- Finca: {$this->farm->name}")
            ->line('- Estado: En preparación')
            ->line("- Fecha: {$orderDate}")
            ->line("- Total: USD {$total}")
            ->action('Ver mis pedidos', url('/buyer/orders'))
            ->line('Puedes ingresar a tu portal FEXIMAR para revisar el estado de tu pedido.')
            ->salutation("FEXIMAR\nPremium Flowers From Ecuador");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'fulfillment_id' => $this->fulfillment->id,
            'farm_id' => $this->farm->id,
            'buyer_id' => $this->buyer->id,
        ];
    }
}
