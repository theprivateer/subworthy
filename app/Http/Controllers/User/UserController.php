<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function show($username = null)
    {
        if (empty($username)) {
            abort(404);
        }

        $user = User::where('username', $username)->firstOrFail();

        $user->load('subscriptions', 'subscriptions.feed');

        return view('user.user.show', [
            'user' => $user,
        ]);

    }

    public function edit()
    {
        $user = auth()->user();

        // Timezones
        $timezone = timezone_list();

        // Timeslots
        $startTime = strtotime('00:00');
        $endTime = strtotime('23:59');
        $returnTimeFormat = 'g:i A';

        $current = time();
        $addTime = strtotime('+'.'15 mins', $current);
        $diff = $addTime - $current;

        $times = [];
        while ($startTime < $endTime) {
            $times[date('Hi', $startTime)] = date($returnTimeFormat, $startTime);
            $startTime += $diff;
        }
        $times[date('Hi', $startTime)] = date($returnTimeFormat, $startTime);

        return view('user.user.edit', [
            'user' => $user,
            'timezone' => $timezone,
            'times' => $times,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $this->validate($request, [
            'email' => ['required', 'email', 'unique:users,email,'.auth()->id()],
            'username' => ['nullable', 'string', 'unique:users,username,'.auth()->id()],
        ]);

        $user = auth()->user();

        $emailIsChanging = $validated['email'] !== $user->email;

        $user->fill($validated);

        // The whole authenticated area sits behind the 'verified' middleware, so leaving
        // email_verified_at in place would let someone move their account to an address they
        // do not control and stay verified — and have issues delivered there. Clearing it
        // means the new address has to be confirmed before delivery resumes.
        if ($emailIsChanging) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailIsChanging) {
            $user->sendEmailVerificationNotification();

            flash('Account updated. Check your new address for a verification link.');

            return back();
        }

        flash('Account updated');

        return back();
    }

    public function destroy(Request $request)
    {
        $user = auth()->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $user->delete();

        return redirect('/cancelled');
    }
}
