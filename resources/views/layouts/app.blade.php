<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite(['resources/scss/style.scss', 'resources/js/app.js'])
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">

    <title>Subworthy</title>

      {!! $headTags ?? '' !!}
  </head>
  <body>

    @include('layouts.navigation')

    <main>
        @include('partials.flash-message')
        {{ $slot }}
    </main>

    {!! $scriptTags ?? '' !!}
  </body>
</html>
