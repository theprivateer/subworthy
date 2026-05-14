<?php

namespace App\Http\Controllers;

use App\Actions\SubscribeToFeed;
use App\Jobs\ProcessOpmlImport;
use App\Reader\GuzzleClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Laminas\Feed\Reader\Reader;

class FeedController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, SubscribeToFeed $subscribeToFeed): RedirectResponse|View
    {
        $data = $request->validate(['url' => 'required|string']);

        Reader::setHttpClient(new GuzzleClient());

        try
        {
            $this->result = Reader::import($data['url']);

            // proceed to subscribing...
        } catch (\Exception $e)
        {
            try {
                $feedLinks = Reader::findFeedLinks($data['url']);

                if(count($feedLinks) == 1)
                {
                    $data['url'] = (string) $feedLinks[0]['href'];
                } else
                {
                    return view('feed.create', [
                        'feedLinks' => $feedLinks
                    ]);
                }

                // If there is only one link, use that
                // Otherwise show a page with feed options
            } catch (\Exception $e)
            {
                $error = $e->getMessage();

                if(strpos($error, '403 Forbidden'))
                {
                    $error = 'Subscribing to ' . $data['url'] . ' resulted in a \'403 Forbidden\' response';
                } else {
                    $error = strip_tags($error);
                    $error = trim($error);
                    $error = trim($error, ':');
                }

                $error .= '.  Contact support@subworthy.com for further assistance.';

                return back()->withErrors($error);
            }
        }

        $subscription = $subscribeToFeed($request->user()->id, $data['url']);

        if($subscription->wasRecentlyCreated === true)
        {
            flash('Subscription created');
        } else
        {
            flash('A Subscription to this Feed already exists');
        }

        return redirect()->route('home');
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opml' => [
                'required',
                File::types(['opml', 'xml'])->max('1mb'),
            ],
        ]);

        // Persist the upload only long enough for the parser job to read it; the job is
        // responsible for deleting the file after success or failure.
        $path = $data['opml']->store('opml-imports', 'local');

        dispatch(new ProcessOpmlImport($request->user()->id, $path));

        flash('OPML import queued. Your subscriptions will appear as the import runs.');

        return redirect()->route('home');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }
}
