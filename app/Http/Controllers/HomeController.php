<?php

namespace App\Http\Controllers;

use App\Models\Issue;

class HomeController extends Controller
{
    public function index()
    {
        $subscriptions = auth()->user()->subscriptions;

        $subscriptions->load('feed');

        $issues = Issue::where('user_id', auth()->id())->latest()->take(7)->get();

        return view('home', [
            'subscriptions' => $subscriptions,
            'issues' => $issues,
        ]);
    }
}
