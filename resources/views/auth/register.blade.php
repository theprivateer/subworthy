<x-guest-layout>
    <div class="row justify-content-center mt-4">
        <div class="col-md-7 col-xl-4 col-lg-5">

            <div class="d-flex justify-content-center py-4">
                <a href="{{ url('/') }}">
                    <x-subworthy-logo size="64" />
                </a>
            </div>

            <h1 class="fw-bold h3 text-center mb-3">Register</h1>

            <div class="card">
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="card-body">

                        @include('partials.validation-errors-warning')

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
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Password" >
                            <label for="password">{{ __('Password') }}</label>

                            @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Confirm Password" >
                            <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <a class="text-muted small me-2" href="{{ route('login') }}">
                            {{ __('Already have an account?') }}
                        </a>

                        <button type="submit" class="btn btn-outline-dark">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
