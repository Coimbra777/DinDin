@extends('cms.layouts.finance-shell')

@section('content')
<div
  id="finance-app"
  class="finance-spa-root"
  data-initial-view="{{ $initialView }}"
  data-initial-month="{{ $month }}"
  data-api-base="/cms/finance/api"
  data-user-name="{{ Auth::user()->name }}"
  data-onboarding-completed="{{ Auth::user()->onboarding_completed ? '1' : '0' }}"
  data-onboarding-complete-url="{{ route('finance.onboarding.complete', [], false) }}"
  data-is-admin="{{ Auth::user()->isAdmin() ? '1' : '0' }}"
></div>
@endsection
