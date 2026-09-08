<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Farm;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function farms(Request $request): StreamedResponse
    {
        $query = Farm::query()
            ->with(['province:id,name', 'city:id,name'])
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('commercial_name', 'like', "%{$q}%")
                    ->orWhere('ruc', 'like', "%{$q}%");
            });
        }
        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return $this->csv('feximar-fincas.csv', [
            'id', 'name', 'commercial_name', 'ruc', 'email', 'phone', 'province', 'city', 'active',
        ], function () use ($query) {
            foreach ($query->cursor() as $farm) {
                yield [
                    $farm->id,
                    $farm->name,
                    $farm->commercial_name,
                    $farm->ruc,
                    $farm->email,
                    $farm->phone,
                    $farm->province?->name,
                    $farm->city?->name,
                    $farm->active ? 1 : 0,
                ];
            }
        });
    }

    public function buyers(Request $request): StreamedResponse
    {
        $query = Buyer::query()->with('country:id,name')->orderBy('company_name');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('company_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%");
            });
        }

        return $this->csv('feximar-compradores.csv', [
            'id', 'company_name', 'contact_name', 'email', 'country', 'credit_allowed', 'credit_days_default', 'active',
        ], function () use ($query) {
            foreach ($query->cursor() as $buyer) {
                yield [
                    $buyer->id,
                    $buyer->company_name,
                    $buyer->contact_name,
                    $buyer->email,
                    $buyer->country?->name ?? $buyer->getAttributes()['country'] ?? '',
                    $buyer->credit_allowed ? 1 : 0,
                    $buyer->credit_days_default,
                    $buyer->active ? 1 : 0,
                ];
            }
        });
    }

    public function orders(Request $request): StreamedResponse
    {
        $query = Order::query()
            ->with(['buyer:id,company_name', 'destinationCountry:id,name', 'cargoAgency:id,name'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('buyer_id')) {
            $query->where('buyer_id', $request->integer('buyer_id'));
        }
        if ($request->filled('payment_condition')) {
            $query->where('payment_condition', $request->string('payment_condition')->toString());
        }
        if ($request->filled('shipping_method')) {
            $query->where('shipping_method', $request->string('shipping_method')->toString());
        }

        return $this->csv('feximar-pedidos.csv', [
            'id', 'buyer', 'status', 'total', 'payment_condition', 'shipping_method', 'destination_country', 'cargo_agency', 'created_at',
        ], function () use ($query) {
            foreach ($query->cursor() as $order) {
                yield [
                    $order->id,
                    $order->buyer?->company_name,
                    $order->status,
                    $order->total,
                    $order->payment_condition,
                    $order->shipping_method,
                    $order->destinationCountry?->name,
                    $order->cargoAgency?->name,
                    $order->created_at?->toDateTimeString(),
                ];
            }
        });
    }

    public function availabilities(Request $request): StreamedResponse
    {
        $query = DB::table('farm_product_availabilities as fpa')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('farms as f', 'f.id', '=', 'fp.farm_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->orderByDesc('fpa.id')
            ->select([
                'fpa.id',
                'f.name as farm',
                'p.name as product',
                'p.variety',
                'fpp.stem_length_cm',
                'fpa.year',
                'fpa.week_number',
                'fpa.available_stems',
                'fpa.reserved_stems',
                'fpa.price_per_stem',
                'fpa.active',
            ]);

        if ($request->filled('farm_id')) {
            $query->where('f.id', $request->integer('farm_id'));
        }
        if ($request->filled('year')) {
            $query->where('fpa.year', $request->integer('year'));
        }
        if ($request->filled('week')) {
            $query->where('fpa.week_number', $request->integer('week'));
        }

        $rows = $query->get();

        return $this->csv('feximar-disponibilidades.csv', [
            'id', 'farm', 'product', 'variety', 'stem_length_cm', 'year', 'week', 'available_stems', 'reserved_stems', 'price_per_stem', 'active',
        ], function () use ($rows) {
            foreach ($rows as $row) {
                yield [
                    $row->id,
                    $row->farm,
                    $row->product,
                    $row->variety,
                    $row->stem_length_cm,
                    $row->year,
                    $row->week_number,
                    $row->available_stems,
                    $row->reserved_stems,
                    $row->price_per_stem,
                    $row->active ? 1 : 0,
                ];
            }
        });
    }

    public function products(Request $request): StreamedResponse
    {
        $query = Product::query()->orderBy('name');
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where('name', 'like', "%{$q}%");
        }

        return $this->csv('feximar-productos.csv', [
            'id', 'name', 'category', 'variety', 'color', 'image_path', 'active',
        ], function () use ($query) {
            foreach ($query->cursor() as $product) {
                yield [
                    $product->id,
                    $product->name,
                    $product->category,
                    $product->getAttributes()['variety'] ?? '',
                    $product->color,
                    $product->image_path,
                    $product->active ? 1 : 0,
                ];
            }
        });
    }

    public function sales(Request $request): StreamedResponse
    {
        $query = DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->join('buyers as b', 'b.id', '=', 'o.buyer_id')
            ->join('farm_product_availabilities as fpa', 'fpa.id', '=', 'od.farm_product_availability_id')
            ->join('farm_product_presentations as fpp', 'fpp.id', '=', 'fpa.farm_product_presentation_id')
            ->join('farm_products as fp', 'fp.id', '=', 'fpp.farm_product_id')
            ->join('farms as f', 'f.id', '=', 'fp.farm_id')
            ->join('products as p', 'p.id', '=', 'fp.product_id')
            ->where('o.status', '!=', 'cancelled')
            ->orderByDesc('o.id')
            ->select([
                'o.id as order_id',
                'o.created_at',
                'b.company_name as buyer',
                'f.name as farm',
                'p.name as product',
                'p.variety',
                'od.bunches',
                'od.total_stems',
                'od.boxes',
                'od.price_per_stem',
                'od.subtotal',
            ]);

        if ($request->filled('farm_id')) {
            $query->where('f.id', $request->integer('farm_id'));
        }
        if ($request->filled('buyer_id')) {
            $query->where('b.id', $request->integer('buyer_id'));
        }
        if ($request->filled('date_from')) {
            $query->where('o.created_at', '>=', $request->string('date_from')->toString().' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('o.created_at', '<=', $request->string('date_to')->toString().' 23:59:59');
        }

        $rows = $query->get();

        return $this->csv('feximar-ventas.csv', [
            'order_id', 'created_at', 'buyer', 'farm', 'product', 'variety', 'bunches', 'total_stems', 'boxes', 'price_per_stem', 'subtotal',
        ], function () use ($rows) {
            foreach ($rows as $row) {
                yield [
                    $row->order_id,
                    $row->created_at,
                    $row->buyer,
                    $row->farm,
                    $row->product,
                    $row->variety,
                    $row->bunches,
                    $row->total_stems,
                    $row->boxes,
                    $row->price_per_stem,
                    $row->subtotal,
                ];
            }
        });
    }

    public function payments(): StreamedResponse
    {
        $rows = DB::table('farm_payments as fp')
            ->join('farm_order_finances as fof', 'fof.id', '=', 'fp.farm_order_finance_id')
            ->join('farms as f', 'f.id', '=', 'fof.farm_id')
            ->orderByDesc('fp.id')
            ->get([
                'fp.id',
                'fof.order_id',
                'f.name as farm',
                'fp.amount',
                'fp.payment_date',
                'fp.reference',
                'fp.notes',
            ]);

        return $this->csv('feximar-pagos.csv', [
            'id', 'order_id', 'farm', 'amount', 'payment_date', 'reference', 'notes',
        ], function () use ($rows) {
            foreach ($rows as $row) {
                yield [
                    $row->id,
                    $row->order_id,
                    $row->farm,
                    $row->amount,
                    $row->payment_date,
                    $row->reference,
                    $row->notes,
                ];
            }
        });
    }

    /**
     * @param  list<string>  $headers
     * @param  callable():\Generator  $rows
     */
    private function csv(string $filename, array $headers, callable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows() as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
