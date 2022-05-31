<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();

        $user->update([
            'timezone' => $request->get('timezone'),
            'delivery_time_local' => $request->get('delivery_time_local'),
            'days_of_week' => implode('', $request->get('days_of_week', [])),
        ]);

        flash('Delivery details updated');

        return back();
    }
}
