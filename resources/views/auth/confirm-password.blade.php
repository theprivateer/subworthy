<x-guest-layout>
    <div class="row justify-content-center mt-4">

        <div class="col-md-7 col-xl-4 col-lg-5">

            <div class="d-flex justify-content-center py-4">
                <a href="{{ url('/') }}">
                    <x-subworthy-logo size="64" />
                </a>
            </div>

            <div class="card">
                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf
                    <div class="card-body">
                        @include('partials.validation-errors-warning', ['message' => 'Your password was incorrect'])

                        <p class="mb-3">
                            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                        </p>

                        <!-- Password -->
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="Password" >
                            <label for="password">{{ __('Password') }}</label>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end align-items-center">
                        <button type="submit" class="btn btn-outline-dark">{{ __('Confirm') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
