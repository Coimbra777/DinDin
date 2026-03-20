@extends('cms.layouts.finance-shell')

@section('content')
<div
  id="finance-app"
  class="finance-spa-root"
  data-initial-view="{{ $initialView }}"
  data-initial-month="{{ $month }}"
  data-api-base="{{ url('/cms/finance/api') }}"
  data-user-name="{{ Auth::user()->name }}"
></div>
@endsection
