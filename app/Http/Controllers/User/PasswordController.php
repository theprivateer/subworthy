<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function update(Request $request)
    {
        $validated = $this->validate($request, [
            'password' => 'required|confirmed|min:8',
        ]);

        $user = auth()->user();

        $user->update([
            'password' => bcrypt($validated['password']),
        ]);

        flash('Password updated');

        return back();
    }
}
