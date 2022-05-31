<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

      <link rel="stylesheet" href="https://use.typekit.net/rjz3xri.css">
      <link href="{{ asset('css/style.css') }}" rel="stylesheet">

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


    <script src="{{ asset('js/app.js') }}"></script>
    {!! $scriptTags ?? '' !!}
  </body>
</html>
