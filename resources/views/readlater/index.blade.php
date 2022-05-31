<x-guest-layout>


    <div class="container-fluid">

        <div class="row justify-content-center py-4">
            <div class="col-md-4 col-lg-3">
                <div  class="position-sticky top-0 pt-2">
                    <div class="d-flex align-items-center mb-2">
                        <a href="{{ route('home') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-app-indicator" viewBox="0 0 16 16">
                                <path d="M5.5 2A3.5 3.5 0 0 0 2 5.5v5A3.5 3.5 0 0 0 5.5 14h5a3.5 3.5 0 0 0 3.5-3.5V8a.5.5 0 0 1 1 0v2.5a4.5 4.5 0 0 1-4.5 4.5h-5A4.5 4.5 0 0 1 1 10.5v-5A4.5 4.5 0 0 1 5.5 1H8a.5.5 0 0 1 0 1H5.5z"/>
                                <path d="M16 3a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            </svg>
                        </a>

                        <h1 class="fs-4 ms-3 my-0">Read Later</h1>
                    </div>

                    <ul>
                        @foreach($posts as $feed)
                            <li>
                                <a href="#{{ $feed->first()->post->feed->id }}" class="text-decoration-none">{{ $feed->first()->post->feed_title ?? $feed->first()->post->feed->title }}</a>
                            </li>
                        @endforeach
                    </ul>

                </div>
            </div>



            <div class="col-md-8 col-lg-7">
                @foreach($posts as $feed)
                    <div class="mb-4 pb-4">
                        <h2 id="{{ $feed->first()->post->feed->id }}" class="h3 fw-bold pt-2">
                            {{ $feed->first()->post->feed_title ?? $feed->first()->post->feed->title }}

                            <a href="{{ $feed->first()->post->feed->link }}" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                                    <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                                </svg>
                            </a>
                        </h2>

                        @foreach($feed as $post)
                            <livewire:article :post="$post->post" :user="auth()->user()" :authUser="auth()->user()" :readLater="$post" :showRemove="true" :showExpiry="true" />
                        @endforeach
                    </div>

                @endforeach
            </div>
        </div>
    </div>

    <div class="d-block d-sm-block d-md-none position-fixed bottom-0 start-0 ps-2 pb-2">
        <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
            </svg>
        </button>
    </div>


    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                <div class="d-flex align-items-center mb-2">
                    <a href="{{ route('home') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-app-indicator" viewBox="0 0 16 16">
                            <path d="M5.5 2A3.5 3.5 0 0 0 2 5.5v5A3.5 3.5 0 0 0 5.5 14h5a3.5 3.5 0 0 0 3.5-3.5V8a.5.5 0 0 1 1 0v2.5a4.5 4.5 0 0 1-4.5 4.5h-5A4.5 4.5 0 0 1 1 10.5v-5A4.5 4.5 0 0 1 5.5 1H8a.5.5 0 0 1 0 1H5.5z"/>
                            <path d="M16 3a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        </svg>
                    </a>

                    <h1 class="fs-4 ms-3 my-0">Read Later</h1>
                </div>

            </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul>
                @foreach($posts as $feed)
                    <li>
                        <a href="#{{ $feed->first()->post->feed->id }}" class="text-decoration-none" onclick="hideOffCanvas()">{{ $feed->first()->post->feed->title }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <button id="close-btn" class="btn btn-lg position-fixed top-0 end-0 p-0 pe-2 pt-2" onclick="closeArticle()" style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
        </svg>
    </button>

    <x-slot name="headTags">
        <link rel="stylesheet" href="{{ asset('js/styles/atom-one-light.min.css') }}">
        @livewireStyles
    </x-slot>

    <x-slot name="scriptTags">
        <script src="{{ asset('js/highlight.min.js') }}"></script>

        @livewireScripts

        <script>
            let currentlyOpen

            function hideOffCanvas()
            {
                let el = document.getElementById('offcanvasExample')
                window.bootstrap.Offcanvas.getInstance(el).hide()
            }

            Livewire.on('postOpened', postUuid => {
                hljs.highlightAll()
                document.getElementById(postUuid).scrollIntoView()

                closeArticle()

                currentlyOpen = postUuid
                document.getElementById("close-btn").style.display = "block"
            })

            Livewire.on('postClosed', postUuid => {
                if(currentlyOpen == postUuid)
                {
                    document.getElementById(currentlyOpen).scrollIntoView()
                    currentlyOpen = null
                    document.getElementById("close-btn").style.display = "none"
                } else {
                    document.getElementById(currentlyOpen).scrollIntoView()
                }

            })

            function closeArticle()
            {
                if(currentlyOpen != null)
                {
                    let el = document.getElementById(currentlyOpen).getAttribute('wire:id')
                    let component = Livewire.find([el])

                    component.showPreview()
                }
            }
        </script>
    </x-slot>
</x-guest-layout>
