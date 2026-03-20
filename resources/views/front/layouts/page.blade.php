@extends('front.layouts.app')

@section('body')
  <div id="app">
    <header>
      Bem-vindo ao Meu Site
    </header>
    <section class="content">
      @yield('content')
    </section>
    <footer>
      Footer
    </footer>
  </div>
@endsection
