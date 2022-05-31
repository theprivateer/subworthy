@component('mail::message')
# Subworthy
## Issue {{ $issue->edition }} - {{ $issue->issue_date->format('l j F, Y') }}

@component('mail::button', ['url' => route('issue', $issue)])
View Issue Online
@endcomponent

@if($user->hasDefaultDeliverySettings())
@component('mail::panel')
**Did you know?** You can customise the days and time Subworthy delivers your daily email? <a href="{{ route('user.edit') }}#delivery">Click here...</a>
@endcomponent
@endif

@foreach($posts as $feed)
## {{ $feed->first()->feed_title ?? $feed->first()->feed->title }}

@foreach($feed as $post)
* [{{ $post->title }}]({{ route('link', [$user, $post]) }})
@endforeach
@endforeach

@component('mail::button', ['url' => route('issue', $issue)])
View Issue Online
@endcomponent

@endcomponent
