@extends('cms.layouts.app')

@section('body')

<body class="hold-transition login-page fundo">
  <div class="container">
    <div class="row h-100 align-items-center justify-content-center">
      <div class="login-box col-12" style="max-width: 420px;">
        <div class="login-logo col-12">
          <img src="{{ asset('img/logo_cms.svg') }}" alt="{{ config('app.name') }}">
        </div>
        <div class="login-box-body col-12" style="border-radius:0.25rem">
          <div class="row">
            <h4 class="text-center col-12">Criar conta</h4>
            <p class="text-center text-muted col-12 small">WhatsApp com DDD (será normalizado ao salvar)</p>

            <form class="form-horizontal col-12" method="POST" action="{{ route('register') }}">
              {{ csrf_field() }}

              <div class="form-group{{ $errors->has('name') ? ' has-warning' : '' }}">
                <label class="control-label" for="name">Nome completo</label>
                <input id="name" name="name" type="text" class="form-control" value="{{ old('name') }}" required autofocus>
                @if ($errors->has('name'))
                  <span class="help-block"><strong>{{ $errors->first('name') }}</strong></span>
                @endif
              </div>

              <div class="form-group{{ $errors->has('email') ? ' has-warning' : '' }}">
                <label class="control-label" for="email">E-mail</label>
                <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" required>
                @if ($errors->has('email'))
                  <span class="help-block"><strong>{{ $errors->first('email') }}</strong></span>
                @endif
              </div>

              <div class="form-group{{ $errors->has('whatsapp') ? ' has-warning' : '' }}">
                <label class="control-label" for="whatsapp">WhatsApp</label>
                <input id="whatsapp" name="whatsapp" type="text" class="form-control" value="{{ old('whatsapp') }}"
                  placeholder="(98) 99999-9999" required autocomplete="tel">
                @if ($errors->has('whatsapp'))
                  <span class="help-block"><strong>{{ $errors->first('whatsapp') }}</strong></span>
                @endif
              </div>

              <div class="form-group{{ $errors->has('password') ? ' has-warning' : '' }}">
                <label class="control-label" for="password">Senha</label>
                <input id="password" name="password" type="password" class="form-control" required
                  minlength="8" autocomplete="new-password">
                <span class="help-block small">Mínimo 8 caracteres, com letras e números.</span>
                @if ($errors->has('password'))
                  <span class="help-block"><strong>{{ $errors->first('password') }}</strong></span>
                @endif
              </div>

              <div class="form-group{{ $errors->has('password_confirmation') ? ' has-warning' : '' }}">
                <label class="control-label" for="password_confirmation">Confirmar senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required
                  minlength="8" autocomplete="new-password">
                @if ($errors->has('password_confirmation'))
                  <span class="help-block"><strong>{{ $errors->first('password_confirmation') }}</strong></span>
                @endif
              </div>

              <div class="row">
                <div class="col-12">
                  <button type="submit" class="btn btn-success btn-block btn-flat" style="border-radius:0.25rem">
                    Cadastrar
                  </button>
                </div>
                <div class="col-12 text-center" style="margin-top: 12px;">
                  <a href="{{ route('login') }}">Já tenho conta</a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
  <script>
    $(function () {
      $('#whatsapp').mask('(00) 00000-0000');
    });
  </script>
</body>

@endsection
