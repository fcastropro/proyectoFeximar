<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? $request->user()->only(['id', 'name', 'email'])
                    : null,
            ],
            'farmPortal' => function () use ($request) {
                $farm = $request->attributes->get('currentFarm');

                if (! $farm) {
                    $user = $request->user();

                    if (! $user || ! $user->isFarmUser()) {
                        return null;
                    }

                    $farm = $user->primaryFarm();
                }

                if (! $farm) {
                    return null;
                }

                return [
                    'id' => $farm->id,
                    'name' => $farm->name,
                ];
            },
            'buyerPortal' => function () use ($request) {
                $buyer = $request->attributes->get('currentBuyer');

                if (! $buyer) {
                    $user = $request->user();

                    if (! $user || ! $user->isBuyerUser()) {
                        return null;
                    }

                    $buyer = $user->primaryBuyer();
                }

                if (! $buyer) {
                    return null;
                }

                return [
                    'id' => $buyer->id,
                    'name' => $buyer->company_name,
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
