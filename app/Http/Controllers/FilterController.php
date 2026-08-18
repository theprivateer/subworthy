<?php

namespace App\Http\Controllers;

use App\Models\Filter;
use App\Models\Subscription;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    public function store(Request $request, Subscription $subscription)
    {
        // Filters decide what is excluded from an issue, so writing one to a subscription
        // you do not own lets you silently empty someone else's newsletter.
        abort_if($subscription->user_id !== auth()->id(), 404);

        $this->validate($request, [
            'field' => 'required',
            'operator' => 'required',
            'pattern' => 'required',
        ]);

        $filter = new Filter($request->only(['field', 'operator', 'pattern']));

        $subscription->filters()->save($filter);

        flash('Filter created');

        return back();
    }

    public function update(Request $request, Filter $filter)
    {
        $this->authoriseFilter($filter);

        $validated = $request->validate([
            'field_'.$filter->id => 'required',
            'operator_'.$filter->id => 'required',
            'pattern_'.$filter->id => 'required',
        ]);

        $filter->update([
            'field' => $validated['field_'.$filter->id],
            'operator' => $validated['operator_'.$filter->id],
            'pattern' => $validated['pattern_'.$filter->id],
        ]);

        flash('Filter updated');

        return back();
    }

    public function destroy(Request $request, Filter $filter)
    {
        $this->authoriseFilter($filter);

        $filter->delete();

        flash('Filter deleted');

        return back();
    }

    /**
     * A filter is owned through its subscription, so ownership is checked one hop up.
     * 404 rather than 403 matches SubscriptionController and avoids confirming that a
     * given filter id exists.
     */
    private function authoriseFilter(Filter $filter): void
    {
        abort_if($filter->subscription?->user_id !== auth()->id(), 404);
    }
}
