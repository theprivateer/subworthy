<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">

    <title>Subworthy</title>

      {!! $headTags ?? '' !!}

      <meta property="og:title" content="Subworthy" />
      <meta property="og:description" content="Subworthy is a free online RSS reader that compiles all of your subscriptions into a single daily email." />
      <meta property="og:type" content="website" />
      <meta property="og:image" content="{{ asset('img/opengraph-image.png') }}" />

      <meta name="twitter:title" content="Subworthy" />
      <meta name="twitter:description" content="Subworthy is a free online RSS reader that compiles all of your subscriptions into a single daily email." />
      <meta name="twitter:site" content="@SubworthyApp" />
      <meta name="twitter:image" content="{{ asset('img/twitter-card-image.png') }}" />
  </head>
  <body>

    <main>
        {{ $slot }}
    </main>

    <script src="{{ asset('js/app.js') }}"></script>

    {!! $scriptTags ?? '' !!}
  </body>
</html>
