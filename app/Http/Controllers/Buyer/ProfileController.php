<?php

namespace App\Http\Controllers\Buyer;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends BaseBuyerController
{
    public function show(Request $request): Response
    {
        $buyer = $this->currentBuyer($request);
        $user = $request->user();

        return Inertia::render('Buyer/Profile', [
            'profile' => [
                'company_name' => $buyer->company_name,
                'contact_name' => $buyer->contact_name,
                'email' => $buyer->email,
                'phone' => $buyer->phone,
                'country' => $buyer->country,
                'city' => $buyer->city,
                'address' => $buyer->address,
                'credit_allowed' => (bool) $buyer->credit_allowed,
                'credit_days_default' => $buyer->credit_days_default,
                'user_name' => $user->name,
                'user_email' => $user->email,
            ],
        ]);
    }
}
