<x-guest-layout>


<div class="row justify-content-center mt-4">
    <div class="col-md-4">
        <div class="d-flex justify-content-center py-4">
            <a href="{{ url('/') }}">
                <x-subworthy-logo size="64" />
            </a>
        </div>

        <div class="card">

            <!-- TODO: Session Status -->

            <!-- TODO: Validation Errors -->
            @include('partials.validation-errors-warning')

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                    </div>

                    <x-honeypot />

                    <!-- Email Address -->
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="name@example.com" >
                        <label for="email">{{ __('Email Address') }}</label>

                        @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end align-items-center">
                    <button type="submit" class="btn btn-outline-dark">
                        {{ __('Email Password Reset Link') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-guest-layout>
