@php
  $authPage = $authPage ?? 'login';
  $resetToken = $token ?? '';
  $resetEmail = $email ?? '';
  $authStatus = session('status') ? (string) session('status') : '';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="cms-auth-html">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" href="{{ asset('logowhite.png') }}" type="image/png">

  <title>{{ config('app.name') }} |
    @if ($authPage === 'register')
      Criar conta
    @elseif ($authPage === 'forgot')
      Recuperar senha
    @elseif ($authPage === 'reset')
      Nova senha
    @else
      Entrar
    @endif
  </title>

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
    data-forgot-submit-url="{{ route('cms.forgot-password') }}"
    data-reset-submit-url="{{ route('cms.reset-password') }}"
    data-reset-token="{{ e($resetToken) }}"
    data-reset-email="{{ e($resetEmail) }}"
    data-auth-status="{{ e($authStatus) }}"
    data-logo-url="{{ asset('logowhite.png') }}"
    data-app-name="{{ e(config('app.name')) }}"
    data-errors='@json((object) $errors->getMessages())'
    data-old='@json((object) (old() ?: []))'
  ></div>
</body>

</html>
