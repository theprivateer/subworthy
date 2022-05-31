


<x-guest-layout>


    <div class="row justify-content-center mt-4">
        <div class="col-md-4">
            <div class="d-flex justify-content-center py-4">
                <a href="{{ url('/') }}">
                    <x-subworthy-logo size="64" />
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    @if (session('status') == 'verification-link-sent')
                        <div class="mb-4 alert alert-success">
                            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                        </div>
                    @endif


                    {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-dark">
                            {{ __('Resend Verification Email') }}
                        </button>
                    </form>


                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="btn btn-link">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
