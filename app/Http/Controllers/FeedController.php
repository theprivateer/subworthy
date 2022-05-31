<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use App\Models\Subscription;
use App\Reader\GuzzleClient;
use Illuminate\Http\Request;
use Laminas\Feed\Reader\Reader;
use League\Uri\Uri;

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
    public function store(Request $request)
    {
        // validation on the URL

        $data = $request->all();

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

        // If we get to this point we can proceed with subscription
        $url = rtrim($data['url'], '/');
        $uri = Uri::createFromString($url);
        $scheme = $uri->getScheme();

        $protocol_less_url = str_replace($scheme . '://', '', $url);

        $feed = Feed::where('protocol_less_url', $protocol_less_url)->first();

        if( ! $feed)
        {
            $feed = Feed::create([
                'url'    => $url,
                'protocol_less_url' => $protocol_less_url,
            ]);
        }

        $subscription = Subscription::firstOrCreate([
           'user_id' => auth()->user()->id,
            'feed_id' => $feed->id,
        ]);

        // if this is a new feed record only...
        if($feed->wasRecentlyCreated === true)
        {
            dispatch(new \App\Jobs\CheckFeed($feed, true));
        }

        if($subscription->wasRecentlyCreated === true)
        {
            flash('Subscription created');
        } else
        {
            flash('A Subscription to this Feed already exists');
        }

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
