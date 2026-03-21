@extends('cms.layouts.admin-shell')

@section('content')
<div
  id="admin-app"
  class="finance-spa-root"
  data-api-base="/cms/admin/api"
  data-user-name="{{ Auth::user()->name }}"
  data-finance-panel-url="{{ route('finance_dashboard.index', [], false) }}"
></div>
@endsection
