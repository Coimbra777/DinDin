@extends('cms.layouts.app')

@section('body')
<body class="hold-transition skin-red" style="background:#f4f4f4;">
  <div style="max-width:960px;margin:0 auto;padding:1.5rem;">
    <header class="d-flex justify-content-between align-items-center mb-3">
      <h1 style="margin:0;font-size:1.35rem;">Administração</h1>
      <nav>
        <a href="{{ route('cms.admin.users.index') }}">Utilizadores</a>
        @if(Route::has('finance_dashboard.index'))
          <span class="text-muted"> · </span>
          <a href="{{ route('finance_dashboard.index') }}">Finanças</a>
        @endif
      </nav>
    </header>
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif
    @yield('content')
  </div>
</body>
@endsection
