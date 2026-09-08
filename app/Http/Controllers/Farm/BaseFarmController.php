<?php

namespace App\Http\Controllers\Farm;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\Request;

abstract class BaseFarmController extends Controller
{
    protected function currentFarm(Request $request): Farm
    {
        /** @var Farm $farm */
        $farm = $request->attributes->get('currentFarm');

        return $farm;
    }
}
