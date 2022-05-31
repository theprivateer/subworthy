<x-app-layout>
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-xl-5 col-lg-6">
            <ul class="nav nav-pills nav-fill mb-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Subscriptions</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Issues</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                {{ $error }}
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('feed.create') }}" method="POST">
                        @csrf

                        <div class="d-flex mb-3">
                            <div class="flex-grow-1 form-floating me-2">
                                <input type="text" placeholder="https://example.com" class="form-control @error('url') is-invalid @enderror" id="url" name="url">
                                <label for="url">Type website or feed URL</label>
                            </div>

                            <button type="submit" class="btn btn-primary">Add</button>
                        </div>


                        @error('url')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror

                    </form>

                    @if($subscriptions->count())
                    <div class="list-group">
                        @foreach($subscriptions as $subscription)
                            <div class="list-group-item d-flex">
                                <div class="flex-grow-1 position-relative">
                                    <a href="{{ route('subscription.edit', $subscription) }}" class="stretched-link">{{ $subscription->title ?? $subscription->feed->title }}</a>
                                    @if($subscription->feed->description)
                                        <span class="text-muted d-block small">{!! $subscription->feed->description !!}</span>
                                    @endif
                                </div>

                                <a href="{{ $subscription->feed->website }}" class="btn pe-0" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-globe" viewBox="0 0 16 16">
                                        <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/>
                                    </svg>
                                </a>
                            </div>
{{--                        <a href="{{ route('subscription.edit', $subscription) }}" class="list-group-item list-group-item-action">--}}
{{--                            {{ $subscription->title ?? $subscription->feed->title }}--}}
{{--                            @if($subscription->feed->description)--}}
{{--                                <span class="text-muted d-block small">{!! $subscription->feed->description !!}</span>--}}
{{--                            @endif--}}
{{--                        </a>--}}
                        @endforeach
                    </div>
                    @else
                        <div class="py-4">
                            <p class="h5 text-center py-4 text-muted">Subscribe to your first feed above...</p>
                            <div class="row justify-content-center">
                                <div class="col-md-9">
                                    <img src="{{ asset('img/undraw_save_to_bookmarks_re_8ajf.svg') }}" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    @endif


                </div>

                <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">

                    @forelse($issues as $issue)
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <h4 class="text-muted">#{{ $issue->edition }}</h4>

                                <div class="flex-grow-1 ms-3">
                                    <a href="{{ route('issue', $issue) }}" class="fw-bold stretched-link text-decoration-none">{{ $issue->issue_date->format('l j F, Y') }}</a><br />
                                    {{ count(json_decode($issue->posts, true)) }} {{ Str::of('article')->plural( count(json_decode($issue->posts, true)) ) }}
                                </div>


                            </div>
                        </div>
                    </div>
                    @empty
                        <div class="py-4">

                            <p class="h5 text-center py-4 text-muted">You don't have any daily issues yet</p>
                            <div class="row justify-content-center">
                                <div class="col-md-9">

                                    <img src="{{ asset('img/undraw_messenger_re_8bky.svg') }}" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    @endforelse

                </div>

            </div>
        </div>
    </div>
</div>
</x-app-layout>
