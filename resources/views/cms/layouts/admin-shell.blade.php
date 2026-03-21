<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="robots" content="noindex">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
  <link href="{{ asset('logowhite.png') }}" rel="icon" type="image/png">
  <title>Administração — {{ config('app.name') }}</title>
  @vite(['resources/assets/js/admin/admin-app.js'])
</head>

<body class="finance-standalone-body">
  @yield('content')
  <form id="admin-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
  </form>
</body>

</html>
