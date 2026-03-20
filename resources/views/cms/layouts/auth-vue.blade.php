@php
  $authPage = $authPage ?? 'login';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="cms-auth-html">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" href="{{ url('/img/website/favicon.svg') }}" type="image/svg+xml">

  <title>{{ config('app.name') }} | @if ($authPage === 'register') Criar conta @else Entrar @endif</title>

  @vite(['resources/assets/sass/cms-auth.scss', 'resources/assets/js/cms/auth-app.js'])
</head>

<body>
  <div
    id="cms-auth-app"
    data-page="{{ $authPage }}"
    data-csrf="{{ csrf_token() }}"
    data-login-url="{{ route('login') }}"
    data-register-url="{{ route('register') }}"
    data-forgot-url="{{ route('cms.password.forgot') }}"
    data-logo-url="{{ asset('logowhite.png') }}"
    data-app-name="{{ e(config('app.name')) }}"
    data-errors='@json((object) $errors->getMessages())'
    data-old='@json((object) (old() ?: []))'
  ></div>
</body>

</html>
