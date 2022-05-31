<x-guest-layout>
<div class="container mb-4">
    <div class="row justify-content-center">
        <div class="col-md-9 col-xl-6 col-lg-7">
            @if (Route::has('login'))
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="{{ url('/') }}">
                        <x-subworthy-logo size="32" />
                    </a>

                    <ul class="list-inline my-2">
                        @auth
                            <li class="list-inline-item">
                                <a href="{{ url('/home') }}" class="text-sm text-gray-700 underline">Home</a>
                            </li>
                        @else
                            <li class="list-inline-item">
                                <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">Log in</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="list-inline-item">
                                    <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">Register</a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                </div>
            @endif


                    <h1 class="h3 fw-bold mb-4 text-center">{{ '@' . $user->username }}</h1>


                    @if($user->subscriptions->count())
                    <div class="list-group">
                        @foreach($user->subscriptions as $subscription)
                            <div class="list-group-item d-flex">
                                <div class="flex-grow-1 position-relative">
                                    <a href="{{ $subscription->feed->website }}" class="stretched-link">{{ $subscription->title ?? $subscription->feed->title }}</a>
                                    @if($subscription->feed->description)
                                        <span class="text-muted d-block small">{!! $subscription->feed->description !!}</span>
                                    @endif
                                </div>

                                <a href="{{ $subscription->feed->url }}" class="btn pe-0" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-rss" viewBox="0 0 16 16">
                                        <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                        <path d="M5.5 12a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-3-8.5a1 1 0 0 1 1-1c5.523 0 10 4.477 10 10a1 1 0 1 1-2 0 8 8 0 0 0-8-8 1 1 0 0 1-1-1zm0 4a1 1 0 0 1 1-1 6 6 0 0 1 6 6 1 1 0 1 1-2 0 4 4 0 0 0-4-4 1 1 0 0 1-1-1z"/>
                                    </svg>
                                </a>

                            </div>
                        @endforeach
                    </div>
                    @else
                        <div class="py-4">
                            <p class="h5 text-center py-4 text-muted">Currently not subscribed to any feeds</p>
                            <div class="row justify-content-center">
                                <div class="col-md-9">
                                    <img src="{{ asset('img/undraw_signal_searching_bhpc.svg') }}" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    @endif
        </div>
    </div>
</div>
</x-guest-layout>
