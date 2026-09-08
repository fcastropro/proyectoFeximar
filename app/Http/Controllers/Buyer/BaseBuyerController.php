<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use Illuminate\Http\Request;

abstract class BaseBuyerController extends Controller
{
    protected function currentBuyer(Request $request): Buyer
    {
        /** @var Buyer $buyer */
        $buyer = $request->attributes->get('currentBuyer');

        return $buyer;
    }
}
