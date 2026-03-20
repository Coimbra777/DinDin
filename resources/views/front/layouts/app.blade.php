<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta content="IE=edge" http-equiv="X-UA-Compatible">
  <link href="{{ url('/img/website/favicon.svg') }}" rel="icon" type="image/svg+xml">
  <meta content="{{ csrf_token() }}" name="csrf-token">
  <meta name="robots" content="index" />
  <meta name=theme-color content=#00AD55>
  <meta name=apple-mobile-web-app-status-bar-style content=#00AD55>
  <meta name=msapplication-navbutton-color content=#00AD55>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "{{ config('app.name') }}",
      "url": "{{ rtrim(config('app.url'), '/') }}",
      "logo": "{{ asset('/img/website/share.svg') }}"
    }
  </script>
 
  {{-- @if (!empty($seoToShow))
  <title>{{ $seoToShow['title'] }}</title>
  <meta property="twitter:card" content="summary" />
  <meta property="twitter:site" content="{{ env('CMS_TWITTER_SITE', '') }}" />
  <meta property="twitter:title" content="{{ $seoToShow['title'] }}" />
  <meta property="twitter:description" content="{{ strip_tags($seoToShow['description']) }}" />
  <meta property="twitter:image" content="{{ asset($seoToShow['image']) }}" />
  <meta property="twitter:url" content="{{ $seoToShow['url'] }}" />
  <meta property="og:image" content="{{ asset($seoToShow['image']) }}" />
  <meta property="og:title" content="{{ $seoToShow['title'] }}" />
  <meta property="og:description" content="{{ strip_tags($seoToShow['description']) }}" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ $seoToShow['url'] }}">
  <meta property="og:site_name" content="{{$config->title}}" />
  <meta name="keywords" content="{{$config->keywords}}" />
  <meta name="description" content="{{ strip_tags($seoToShow['description']) }}" />
  @else
  <title>{{$config->title}}</title>
  <meta property="twitter:card" content="summary" />
  <meta property="twitter:site" content="{{ env('CMS_TWITTER_SITE', '') }}" />
  <meta property="twitter:title" content="{{$config->title}}" />
  <meta property="twitter:description" content="{{ strip_tags($config->description) }}" />
  <meta property="twitter:image" content="{{ asset('/img/website/share.svg') }}" />
  <meta property="twitter:url" content="{{ rtrim(config('app.url'), '/') }}" />
  <meta property="og:image" content="{{ asset('/img/website/share.svg') }}" />
  <meta property="og:title" content="{{$config->title}}" />
  <meta property="og:description" content="{{ strip_tags($config->description) }}" />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ rtrim(config('app.url'), '/') }}">
  <meta property="og:site_name" content="{{$config->title}}" />
  <meta name="keywords" content="{{$config->keywords}}" />
  <meta name="description" content="{{ strip_tags($config->description) }}" />
  @endif --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  @vite('resources/assets/sass/website/app.scss')
</head>

<body>
  @yield('body')
</body>
@vite(['resources/assets/js/front/all.js'])
@if(Request::segment(1) == '')
  @vite(['resources/assets/js/front/home.js'])
@endif

@if(Request::segment(1) != '')
  @vite(['resources/assets/js/front/app.js'])
@endif
</html>
