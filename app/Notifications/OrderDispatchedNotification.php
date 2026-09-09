<?php

namespace App\Notifications;

use App\Models\Buyer;
use App\Models\Farm;
use App\Models\Order;
use App\Models\OrderFarmFulfillment;
use App\Support\FeximarMailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDispatchedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly OrderFarmFulfillment $fulfillment,
        public readonly Farm $farm,
        public readonly Buyer $buyer,
        public readonly float $farmTotal,
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

        $eventDate = $this->fulfillment->dispatched_at?->timezone(config('app.timezone'))->format('Y-m-d H:i')
            ?? now()->format('Y-m-d H:i');

        $shippingMethod = match ($this->order->shipping_method) {
            'air' => 'Aéreo',
            'sea' => 'Marítimo',
            default => filled($this->order->shipping_method) ? (string) $this->order->shipping_method : null,
        };

        $destination = collect([
            $this->order->destinationCountry?->name,
            $this->order->destination_city,
        ])->filter()->implode(', ');

        $airportOrPort = match ($this->order->shipping_method) {
            'air' => $this->order->destination_airport,
            'sea' => $this->order->destination_port,
            default => $this->order->destination_airport ?: $this->order->destination_port,
        };

        $actionUrl = url('/buyer/orders');

        return (new MailMessage)
            ->subject('Tu pedido ha sido despachado - FEXIMAR')
            ->view('emails.orders.dispatched', array_merge(FeximarMailBranding::sharedViewData(), [
                'title' => 'Tu pedido ha sido despachado - FEXIMAR',
                'contactName' => $contactName,
                'buyerCompany' => $this->buyer->company_name,
                'orderId' => $this->order->id,
                'farmName' => $this->farm->name,
                'eventDate' => $eventDate,
                'farmTotal' => number_format($this->farmTotal, 2, '.', ','),
                'cargoAgency' => $this->order->cargoAgency?->name,
                'shippingMethod' => $shippingMethod,
                'destination' => $destination !== '' ? $destination : null,
                'airportOrPort' => filled($airportOrPort) ? (string) $airportOrPort : null,
                'actionUrl' => $actionUrl,
            ]));
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
            'farm_total' => $this->farmTotal,
            'status' => 'dispatched',
        ];
    }
}
