<?php

namespace App\Http\Controllers;

use App\Jobs\RemoveUnsubscribedArticlesFromIssues;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function edit(Subscription $subscription)
    {
        $subscription->load('feed', 'filters');

        return view('subscription.edit', [
            'subscription' => $subscription,
        ]);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $subscription->update($validated);

        return back();
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        dispatch(new RemoveUnsubscribedArticlesFromIssues($subscription->user));

        return redirect()->route('home');
    }
}
