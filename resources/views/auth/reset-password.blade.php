
<x-guest-layout>
    <div class="row justify-content-center mt-4">
        <div class="col-md-7 col-xl-4 col-lg-5">

            <div class="d-flex justify-content-center py-4">
                <a href="{{ url('/') }}">
                    <x-subworthy-logo size="64" />
                </a>
            </div>

            <h1 class="fw-bold h3 text-center mb-3">Reset Password</h1>

            <div class="card">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <div class="card-body">

                    @include('partials.validation-errors-warning')

                        <!-- Email Address -->
                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Email Address') }}</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $request->email) }}" required>

                            @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('New Password') }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required >

                            @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Confirm New Password') }}</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required >
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end align-items-center">
                        <button type="submit" class="btn btn-outline-dark">{{ __('Reset Password') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
