<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="cms-auth-html">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" href="{{ asset('logowhite.png') }}" type="image/png">
  <title>Esqueci minha senha | {{ config('app.name') }}</title>
  <style>
    :root { --bg: #2c2f36; --text: #ffffff; --muted: #85888f; --primary: #ff0000; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
      background: radial-gradient(ellipse 100% 80% at 50% 0%, rgba(255, 0, 0, 0.15), transparent 50%),
        linear-gradient(180deg, var(--bg) 0%, #1a1c21 100%);
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .box {
      max-width: 420px;
      text-align: center;
      line-height: 1.55;
    }
    h1 { font-size: 1.35rem; font-weight: 700; margin: 0 0 16px; }
    p { color: var(--muted); margin: 0 0 16px; font-size: 0.95rem; }
    a {
      color: #ff6666;
      font-weight: 600;
      text-decoration: none;
    }
    a:hover { text-decoration: underline; }
  </style>
</head>

<body>
  <div class="box">
    <h1>Esqueci minha senha</h1>
    <p>
      A recuperação automática ainda não está configurada neste painel.
      Contacte o administrador do sistema para redefinir o seu acesso.
    </p>
    <p><a href="{{ route('login') }}">← Voltar ao login</a></p>
  </div>
</body>

</html>
