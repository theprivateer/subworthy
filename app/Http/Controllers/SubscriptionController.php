<?php

namespace App\Http\Controllers;

use App\Jobs\RemoveUnsubscribedArticlesFromIssues;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function edit(Subscription $subscription)
    {
        abort_if($subscription->user_id !== auth()->id(), 404);

        $subscription->load('feed', 'filters');

        return view('subscription.edit', [
            'subscription' => $subscription,
        ]);
    }

    public function update(Request $request, Subscription $subscription)
    {
        abort_if($subscription->user_id !== auth()->id(), 404);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $subscription->update($validated);

        return back();
    }

    public function destroy(Subscription $subscription)
    {
        abort_if($subscription->user_id !== auth()->id(), 404);

        $subscription->delete();

        dispatch(new RemoveUnsubscribedArticlesFromIssues($subscription->user));

        return redirect()->route('home');
    }
}
