<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="utf-8">
  <meta content="IE=edge" http-equiv="X-UA-Compatible">
  <meta content="noindex" name="robots" />
  <link href="{{ url('/img/website/favicon.svg') }}" rel="icon" type="image/svg+xml">

  <meta content="{{ csrf_token() }}" name="csrf-token">

  <title>{{ config('app.name') }} | Painel administrativo</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <!-- Styles -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
  @vite(['resources/assets/sass/app.scss', 'resources/assets/js/app.js', 'resources/assets/js/cms/app.js'])
  @stack('vite-extra')
</head>

<body>
  @yield('body')
</body>

<script>
  $(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
  });
</script>

</html>
