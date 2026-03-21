@extends('cms.layouts.finance-shell')

@section('content')
@php
    $cmsUser = Auth::user();
    /** Sempre reler pivot (evita cache de relação na instância do utilizador). */
    $cmsUser->unsetRelation('saasModules');
    $cmsUser->load('saasModules');
    /** Slugs extra para o menu (o núcleo não usa pivot; `finance` aqui é redundante). */
    $userModuleSlugs = $cmsUser->saasModules
        ->pluck('slug')
        ->reject(fn (string $s) => $s === 'finance')
        ->values()
        ->all();
@endphp
{{-- JSON em atributo data-* quebra com aspas; usar script application/json é fiável. --}}
<script type="application/json" id="finance-user-module-slugs">@json($userModuleSlugs)</script>
<div
  id="finance-app"
  class="finance-spa-root"
  data-initial-view="{{ $initialView }}"
  data-initial-month="{{ $month }}"
  data-api-base="/cms/finance/api"
  data-user-name="{{ $cmsUser->name }}"
  data-onboarding-completed="{{ $cmsUser->onboarding_completed ? '1' : '0' }}"
  data-onboarding-complete-url="{{ route('finance.onboarding.complete', [], false) }}"
  data-is-admin="{{ $cmsUser->isAdmin() ? '1' : '0' }}"
></div>
@endsection
