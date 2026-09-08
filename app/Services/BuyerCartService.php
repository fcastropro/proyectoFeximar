<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\BuyerCart;
use App\Models\BuyerCartItem;
use App\Models\FarmProductAvailability;
use App\Models\PresentationBoxConfig;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuyerCartService
{
    public function activeCart(Buyer $buyer, User $user): BuyerCart
    {
        return BuyerCart::query()->firstOrCreate(
            [
                'buyer_id' => $buyer->id,
                'user_id' => $user->id,
                'status' => BuyerCart::STATUS_ACTIVE,
            ],
            []
        );
    }

    /**
     * @return array{
     *   bunches:int,
     *   stems_per_bunch:int,
     *   total_stems:int,
     *   price_per_stem:float,
     *   subtotal:float
     * }
     */
    public function priceLine(FarmProductAvailability $availability, int $bunches): array
    {
        $availability->loadMissing('presentation');

        $stemsPerBunch = (int) ($availability->presentation?->stems_per_bunch ?? 0);

        if ($stemsPerBunch < 1) {
            throw ValidationException::withMessages([
                'bunches' => 'La presentación no tiene tallos por bunch configurados.',
            ]);
        }

        if ($bunches < 1) {
            throw ValidationException::withMessages([
                'bunches' => 'La cantidad de bunches debe ser al menos 1.',
            ]);
        }

        $pricePerStem = (float) ($availability->price_per_stem ?? 0);
        $totalStems = $bunches * $stemsPerBunch;
        $subtotal = round($totalStems * $pricePerStem, 2);

        return [
            'bunches' => $bunches,
            'stems_per_bunch' => $stemsPerBunch,
            'total_stems' => $totalStems,
            'price_per_stem' => round($pricePerStem, 4),
            'subtotal' => $subtotal,
        ];
    }

    /**
     * Estimación de embalaje sin modificar bunches.
     *
     * @return array{stems_per_box:int,bunches_per_box:?int,boxes:int,partial:bool}
     */
    public function estimatePackaging(FarmProductAvailability $availability, int $boxTypeId, int $totalStems, int $bunches): array
    {
        $config = PresentationBoxConfig::query()
            ->where('farm_product_presentation_id', $availability->farm_product_presentation_id)
            ->where('box_type_id', $boxTypeId)
            ->where('active', true)
            ->whereHas('boxType', fn ($q) => $q->where('active', true))
            ->first();

        if (! $config) {
            throw ValidationException::withMessages([
                'packaging' => 'El tipo de caja no está configurado para esta presentación.',
            ]);
        }

        $stemsPerBox = (int) $config->stems_per_box;
        if ($stemsPerBox < 1) {
            throw ValidationException::withMessages([
                'packaging' => 'La configuración de caja no tiene tallos por caja válidos.',
            ]);
        }

        $boxes = (int) ceil($totalStems / $stemsPerBox);
        $partial = ($totalStems % $stemsPerBox) !== 0;

        return [
            'stems_per_box' => $stemsPerBox,
            'bunches_per_box' => $config->bunches_per_box !== null ? (int) $config->bunches_per_box : null,
            'boxes' => max(1, $boxes),
            'partial' => $partial,
        ];
    }

    public function addItem(Buyer $buyer, User $user, int $availabilityId, int $bunches): BuyerCartItem
    {
        return DB::transaction(function () use ($buyer, $user, $availabilityId, $bunches) {
            $cart = $this->activeCart($buyer, $user);

            $availability = FarmProductAvailability::query()
                ->with('presentation')
                ->whereKey($availabilityId)
                ->where('active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = BuyerCartItem::query()
                ->where('buyer_cart_id', $cart->id)
                ->where('farm_product_availability_id', $availabilityId)
                ->lockForUpdate()
                ->first();

            $newBunches = $bunches + (int) ($existing?->bunches ?? 0);
            $priced = $this->priceLine($availability, $newBunches);

            if ($priced['total_stems'] > $availability->remainingStems()) {
                throw ValidationException::withMessages([
                    'bunches' => 'La cantidad supera la disponibilidad efectiva de tallos.',
                ]);
            }

            if ($existing) {
                $existing->update([
                    'bunches' => $priced['bunches'],
                    'stems_per_bunch_snapshot' => $priced['stems_per_bunch'],
                    'total_stems' => $priced['total_stems'],
                    'price_per_stem_snapshot' => $priced['price_per_stem'],
                    'subtotal' => $priced['subtotal'],
                ]);

                return $existing->fresh();
            }

            return BuyerCartItem::query()->create([
                'buyer_cart_id' => $cart->id,
                'farm_product_availability_id' => $availabilityId,
                'bunches' => $priced['bunches'],
                'stems_per_bunch_snapshot' => $priced['stems_per_bunch'],
                'total_stems' => $priced['total_stems'],
                'price_per_stem_snapshot' => $priced['price_per_stem'],
                'subtotal' => $priced['subtotal'],
            ]);
        });
    }

    public function updateItem(Buyer $buyer, User $user, BuyerCartItem $item, int $bunches): BuyerCartItem
    {
        return DB::transaction(function () use ($buyer, $user, $item, $bunches) {
            $cart = $this->activeCart($buyer, $user);

            if ($item->buyer_cart_id !== $cart->id) {
                abort(403);
            }

            $availability = FarmProductAvailability::query()
                ->with('presentation')
                ->whereKey($item->farm_product_availability_id)
                ->where('active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $priced = $this->priceLine($availability, $bunches);

            if ($priced['total_stems'] > $availability->remainingStems()) {
                throw ValidationException::withMessages([
                    'bunches' => 'La cantidad supera la disponibilidad efectiva de tallos.',
                ]);
            }

            $item->update([
                'bunches' => $priced['bunches'],
                'stems_per_bunch_snapshot' => $priced['stems_per_bunch'],
                'total_stems' => $priced['total_stems'],
                'price_per_stem_snapshot' => $priced['price_per_stem'],
                'subtotal' => $priced['subtotal'],
            ]);

            return $item->fresh();
        });
    }

    public function removeItem(Buyer $buyer, User $user, BuyerCartItem $item): void
    {
        $cart = $this->activeCart($buyer, $user);

        if ($item->buyer_cart_id !== $cart->id) {
            abort(403);
        }

        $item->delete();
    }

    public function clear(Buyer $buyer, User $user): void
    {
        $cart = $this->activeCart($buyer, $user);
        $cart->items()->delete();
    }
}
