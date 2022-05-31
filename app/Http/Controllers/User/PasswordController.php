<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function update(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|confirmed',
        ]);

        $user = auth()->user();

        $user->update([
            'password' => bcrypt($request->get('password'))
        ]);

        flash('Password updated');

        return back();
    }
}
