@extends('cms.layouts.page')

@section('content')
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Nova transação</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('finance_dashboard.index') }}" class="btn btn-default btn-sm">Resumo</a>
                    <a href="{{ route('finance_transactions.index') }}" class="btn btn-default btn-sm">Transações</a>
                </div>
            </div>
            <form method="post" action="{{ route('finance_transactions.store') }}">
                @csrf
                <div class="box-body">
                    @include('cms.finance.transactions._form', ['categories' => $categories])
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('finance_transactions.index') }}" class="btn btn-default">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
