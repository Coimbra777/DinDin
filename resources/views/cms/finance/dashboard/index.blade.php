@extends('cms.layouts.page')

@section('content')
@php
    $available = $stats['available_this_month'];
    $availablePositive = $available >= 0;
@endphp
<div class="row">
    <div class="col-md-12">
        @include('cms.finance.partials.flash-success')

        <div class="box box-default">
            <div class="box-body">
                <form method="get" action="{{ route('finance_dashboard.index') }}" class="form-inline">
                    <label for="month" class="control-label" style="margin-right:8px;">Mês</label>
                    <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                        @include('cms.finance.partials.month-options', ['selected' => $month])
                    </select>
                </form>
            </div>
        </div>

        <div class="jumbotron" style="background: {{ $availablePositive ? '#e8f5e9' : '#ffebee' }}; border: 1px solid {{ $availablePositive ? '#c8e6c9' : '#ffcdd2' }};">
            <p class="lead text-muted" style="margin-bottom: 8px;">Quanto ainda posso gastar este mês</p>
            <h1 style="margin-top: 0; font-size: 42px; font-weight: 600; color: {{ $availablePositive ? '#2e7d32' : '#c62828' }};">
                {{ number_format($available, 2, ',', ' ') }} €
            </h1>
            <p class="text-muted small" style="margin-bottom: 0;">
                Receitas do mês (registadas) menos despesas do mês. Se ainda não registou todas as receitas, este valor fica incompleto.
            </p>
        </div>

        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="info-box bg-aqua">
                    <span class="info-box-icon"><i class="fa fa-money"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Saldo geral</span>
                        <span class="info-box-number">{{ number_format($stats['balance_all_time'], 2, ',', ' ') }} €</span>
                        <span class="info-box-more">Todas as receitas − todas as despesas</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="info-box bg-red">
                    <span class="info-box-icon"><i class="fa fa-arrow-down"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Gasto no mês</span>
                        <span class="info-box-number">{{ number_format($stats['expense_month'], 2, ',', ' ') }} €</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="info-box bg-green">
                    <span class="info-box-icon"><i class="fa fa-arrow-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Receitas no mês</span>
                        <span class="info-box-number">{{ number_format($stats['income_month'], 2, ',', ' ') }} €</span>
                    </div>
                </div>
            </div>
        </div>

        <p>
            <a href="{{ route('finance_transactions.index', ['month' => $month]) }}" class="btn btn-primary">
                <i class="fa fa-list"></i> Ver transações deste mês
            </a>
            <a href="{{ route('finance_transactions.create') }}" class="btn btn-success">
                <i class="fa fa-plus"></i> Nova transação
            </a>
            <a href="{{ route('finance_categories.index') }}" class="btn btn-default">
                Categorias
            </a>
        </p>
    </div>
</div>
@endsection
