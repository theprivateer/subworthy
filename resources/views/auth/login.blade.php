<x-guest-layout>
<div class="row justify-content-center mt-4">

    <div class="col-md-7 col-xl-4 col-lg-5">

        <div class="d-flex justify-content-center py-4">
            <a href="{{ url('/') }}">
                <x-subworthy-logo size="64" />
            </a>
        </div>

        <h1 class="fw-bold h3 text-center mb-3">Log in</h1>


        <div class="card">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="card-body">
                    @include('partials.validation-errors-warning', ['message' => 'Your email or password were incorrect'])

                    <x-honeypot />

                    <!-- Email Address -->
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="name@example.com">
                        <label for="email">{{ __('Email Address') }}</label>

                        @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="Password" >
                        <label for="password">{{ __('Password') }}</label>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="remember_me" name="remember">
                        <label class="form-check-label" for="remember_me">{{ __('Remember me') }}</label>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    @if (Route::has('password.request'))
                        <a class="text-muted small me-2" href="{{ route('password.request') }}">
                            {{ __('Forgot your password?') }}
                        </a>
                    @endif

                    <button type="submit" class="btn btn-outline-dark">Log in</button>
                </div>
            </form>
        </div>

        @if (Route::has('register'))
            <div class="mt-2 text-center">
                <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">Need to register?</a>
            </div>
        @endif
    </div>
</div>
</x-guest-layout>
