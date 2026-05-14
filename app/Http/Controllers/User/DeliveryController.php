<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'timezone' => 'required|string|timezone',
            'delivery_time_local' => 'required|string|regex:/^\d{4}$/',
            'days_of_week' => 'nullable|array',
            'days_of_week.*' => 'integer|between:1,7',
        ]);

        $user = auth()->user();

        $user->update([
            'timezone' => $validated['timezone'],
            'delivery_time_local' => $validated['delivery_time_local'],
            'days_of_week' => implode('', $validated['days_of_week'] ?? []),
        ]);

        flash('Delivery details updated');

        return back();
    }
}
