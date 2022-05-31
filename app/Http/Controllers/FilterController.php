<?php

namespace App\Http\Controllers;

use App\Models\Filter;
use App\Models\Subscription;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    public function store(Request $request, Subscription $subscription)
    {
        $this->validate($request, [
           'field' => 'required',
           'modifier' => 'required',
           'pattern' => 'required',
        ]);

        $filter = new Filter($request->only(['field', 'modifier', 'pattern']));

        $subscription->filters()->save($filter);

        flash('Filter created');

        return back();
    }

    public function update(Request $request, Filter $filter)
    {

        $this->validate($request, [
            'field_' . $filter->id => 'required',
            'modifier_' . $filter->id => 'required',
            'pattern_' . $filter->id => 'required',
        ]);

        $filter->update([
           'field' => $request->get('field_' . $filter->id),
           'modifier' => $request->get('modifier_' . $filter->id),
           'pattern' => $request->get('pattern_' . $filter->id),
        ]);

        flash('Filter updated');

        return back();
    }

    public function destroy(Request $request, Filter $filter)
    {
        $filter->delete();

        flash('Filter deleted');

        return back();
    }
}
