@extends('cms.layouts.page')

@section('content')
<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Editar categoria</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('finance_categories.index') }}" class="btn btn-default btn-sm">Voltar</a>
                </div>
            </div>
            <form method="post" action="{{ route('finance_categories.update', $finance_category) }}">
                @csrf
                @method('PUT')
                <div class="box-body">
                    @include('cms.finance.categories._form', ['finance_category' => $finance_category])
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary">Atualizar</button>
                    <a href="{{ route('finance_categories.index') }}" class="btn btn-default">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
