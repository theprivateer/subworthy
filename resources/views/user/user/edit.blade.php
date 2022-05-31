<x-app-layout back="true">
<div class="container my-4">
    <div class="row justify-content-center mt-4">
        <div class="col-md-8 col-xl-5 col-lg-6">

            <h1 class="h3 fw-bold mb-4 text-center">Edit User Details</h1>

            @include('partials.validation-errors-warning')

            <form method="POST" class="pb-4">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address:</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}">

                    @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label d-flex justify-content-between align-items-center">
                        <span>Public URL Username:</span>
                        <span class="text-muted small">Optional</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text pe-1">{{ config('app.url') }}/@</span>
                        <input type="text" class="form-control ps-1 @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}">
                    </div>

                    <div class="form-text">If you want to share the feeds that you subscribe to you will need a public URL</div>

                    @error('username')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>

            <h3 class="h3 fw-bold pt-4 mb-4 text-center" id="delivery">Delivery Details</h3>

            <form method="POST" action="{{ route('user.delivery') }}" class="pb-4">
                @csrf

                <div class="mb-3">
                    <label for="timezone" class="form-label">Your Timezone:</label>
                    <select id="timezone"  name="timezone" class="form-control" required>
                        @foreach($timezone as $time)
                            <option value="{{$time['zone']}}"
                            @if($time['zone'] == $user->timezone) selected @endif
                            > ({{$time['GMT_difference']. ' ) '.$time['zone']}}</option>
                        @endforeach
                    </select>
                </div>


                <div class="mb-3">
                    <label for="delivery_time_local" class="form-label">Delivery Time:</label>
                    <select id="delivery_time_local"  name="delivery_time_local" class="form-control">
                        @foreach($times as $key => $value)
                            <option value="{{ $key }}"
                                    @if($key == $user->delivery_time_local) selected @endif
                            >{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Delivery Days:</label>

                    @if(empty($user->days_of_week))
                    <div class="alert alert-warning">
                        You will not receive any further emails until you select at least one delivery day.
                    </div>
                    @endif

                    <div class="row">
                        @foreach([
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            7 => 'Sunday',
                        ] as $key => $value)
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="delivery_day_{{ $value }}" name="days_of_week[]" value="{{ $key }}" @if(\Illuminate\Support\Str::contains($user->days_of_week, $key)) checked @endif>
                                    <label class="form-check-label" for="delivery_day_{{ $value }}">{{ __($value) }}</label>
                                </div>
                            </div>
                        @endforeach


                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>

            <h3  class="h3 fw-bold my-4 text-center">Update Password</h3>

            <form method="POST" action="{{ route('user.password') }}">
                @csrf

                <div class="mb-3">
                    <label for="password" class="form-label">New Password:</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">

                    @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm New Password:</label>
                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>

            <div class="mt-4 pt-4">
                <div class="card">
                    <div class="card-header">
                        Cancel your Account
                    </div>

                    <form method="GET" action="{{ route('user.cancel') }}" onsubmit="return confirm('Are you sure you want to cancel your Subworthy account? This cannot be undone.');">
                        <div class="card-body">
                            Cancelling your account will unsubscribe you from all feeds and delete all previous issues.  You will receive no further emails. This cannot be undone.
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-outline-dark">Cancel your Account Now</button>
                        </div>
                    </form>

                </div>
            </div>

        </div>

    </div>
</div>
</x-app-layout>
