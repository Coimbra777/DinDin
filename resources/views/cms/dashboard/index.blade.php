@extends('cms.layouts.page')
@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="box box-default">
      <div class="box-header with-border">
        <h3 class="box-title">Bem-vindo</h3>
      </div>
      <div class="box-body">
        <p>Utilize o menu lateral para aceder aos módulos (páginas, blog, configurações, etc.).</p>
        @if(session()->has('message'))
        <div class="alert alert-success">{{ session()->get('message') }}</div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
