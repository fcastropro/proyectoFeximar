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
use Illuminate\Support\Collection;

class NewOrderForFarmNotification extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $lines
     */
    public function __construct(
        public readonly Order $order,
        public readonly OrderFarmFulfillment $fulfillment,
        public readonly Farm $farm,
        public readonly Buyer $buyer,
        public readonly Collection|array $lines,
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
        $farmGreeting = $this->farm->commercial_name
            ?: $this->farm->name
            ?: 'equipo de finca';

        $orderDate = $this->order->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i')
            ?? now()->format('Y-m-d H:i');

        $lines = collect($this->lines)->values()->all();
        $actionUrl = url('/farm/orders/'.$this->fulfillment->id);

        return (new MailMessage)
            ->subject('Nuevo pedido recibido - FEXIMAR')
            ->view('emails.orders.new-for-farm', array_merge(FeximarMailBranding::sharedViewData(), [
                'title' => 'Nuevo pedido recibido - FEXIMAR',
                'farmGreeting' => $farmGreeting,
                'orderId' => $this->order->id,
                'buyerCompany' => $this->buyer->company_name,
                'orderDate' => $orderDate,
                'orderStatus' => $this->order->status,
                'farmTotal' => number_format($this->farmTotal, 2, '.', ','),
                'lines' => $lines,
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
        ];
    }
}
