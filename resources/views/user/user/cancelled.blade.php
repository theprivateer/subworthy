<x-guest-layout>

    <div class="container homepage-content">

        <div class="row justify-content-center align-items-center">
            <div class="col-lg-7">
                @if (Route::has('login'))
                    <div class="d-flex justify-content-between">
                        <a href="{{ url('/') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-reply" viewBox="0 0 16 16">
                                <path d="M6.598 5.013a.144.144 0 0 1 .202.134V6.3a.5.5 0 0 0 .5.5c.667 0 2.013.005 3.3.822.984.624 1.99 1.76 2.595 3.876-1.02-.983-2.185-1.516-3.205-1.799a8.74 8.74 0 0 0-1.921-.306 7.404 7.404 0 0 0-.798.008h-.013l-.005.001h-.001L7.3 9.9l-.05-.498a.5.5 0 0 0-.45.498v1.153c0 .108-.11.176-.202.134L2.614 8.254a.503.503 0 0 0-.042-.028.147.147 0 0 1 0-.252.499.499 0 0 0 .042-.028l3.984-2.933zM7.8 10.386c.068 0 .143.003.223.006.434.02 1.034.086 1.7.271 1.326.368 2.896 1.202 3.94 3.08a.5.5 0 0 0 .933-.305c-.464-3.71-1.886-5.662-3.46-6.66-1.245-.79-2.527-.942-3.336-.971v-.66a1.144 1.144 0 0 0-1.767-.96l-3.994 2.94a1.147 1.147 0 0 0 0 1.946l3.994 2.94a1.144 1.144 0 0 0 1.767-.96v-.667z"/>
                            </svg>
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

                <div class="d-flex justify-content-center my-4 py-4">
                    <x-subworthy-logo size="100" />
                </div>

                <div class="my-4 pb-4">
                    <p class="h5 text-center py-4 text-muted">Your account has been cancelled.</p>

                    <div class="row justify-content-center">
                        <div class="col-md-9">
                            <img src="{{ asset('img/undraw_with_love_re_1q3m.svg') }}" alt="" class="img-fluid">
                        </div>
                    </div>

                    <p class="h5 text-center py-4 text-muted">Thanks for using <span class="fw-bold">Subworthy</span>. We're sorry to see you go.</p>
                </div>
            </div>
        </div>
    </div>



</x-guest-layout>
