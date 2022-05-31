<?php

namespace App\Http\Controllers;

use App\Models\Issue;

class IssueController extends Controller
{
    public function show(Issue $issue)
    {
        $issue->loadIssue();

        $issue->user->logInteraction();

        $authUser = false;

        if(auth()->check())
        {
            auth()->user()->load('readLaters');
            $authUser = auth()->user();
        }

        return view('issue.show', [
            'issue'     => $issue,
            'posts'     => $issue->issue_posts,
            'user'      => $issue->user,
            'authUser' => $authUser,
        ]);
    }
}
