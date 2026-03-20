@extends('cms.layouts.page')

@section('content')
@php
    $month = $filters['month'];
@endphp
<div class="row">
    <div class="col-md-12">
        @include('cms.finance.partials.flash-success')

        <p class="clearfix">
            <a href="{{ route('finance_dashboard.index', ['month' => $month]) }}" class="btn btn-default btn-sm">
                <i class="fa fa-pie-chart"></i> Resumo e saldo do mês
            </a>
        </p>

        @if($categorySummary !== null)
        @php $av = $categorySummary['available']; @endphp
        <div class="alert alert-{{ $av >= 0 ? 'info' : 'warning' }}">
            <strong>Filtro por categoria ({{ $monthChoices[$month] ?? $month }})</strong><br>
            Gasto: {{ number_format($categorySummary['expense'], 2, ',', ' ') }} € —
            Receitas: {{ number_format($categorySummary['income'], 2, ',', ' ') }} € —
            Líquido: <strong>{{ number_format($av, 2, ',', ' ') }} €</strong>
        </div>
        @endif

        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Filtros</h3>
            </div>
            <div class="box-body">
                <form method="get" action="{{ route('finance_transactions.index') }}" class="form-inline">
                    <div class="form-group" style="margin-right: 12px; margin-bottom: 8px;">
                        <label for="month" style="margin-right:6px;">Mês</label>
                        <select name="month" id="month" class="form-control">
                            @include('cms.finance.partials.month-options', ['selected' => $filters['month'] ?? ''])
                        </select>
                    </div>
                    <div class="form-group" style="margin-right: 12px; margin-bottom: 8px;">
                        <label for="category_id" style="margin-right:6px;">Categoria</label>
                        <select name="category_id" id="category_id" class="form-control">
                            <option value="">Todas</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)($filters['category_id'] ?? '') === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Aplicar</button>
                    <a href="{{ route('finance_transactions.index', ['month' => $filters['month']]) }}" class="btn btn-default">Só este mês</a>
                </form>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Transações</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('finance_transactions.create') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Nova transação
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th class="text-right">Valor</th>
                            <th class="text-right" width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $t)
                        <tr>
                            <td>{{ $t->transaction_date->format('d/m/Y') }}</td>
                            <td>{{ $t->title }}</td>
                            <td>
                                @if($t->type === \App\Modules\Finance\Models\Transaction::TYPE_INCOME)
                                <span class="label label-success">Receita</span>
                                @else
                                <span class="label label-danger">Despesa</span>
                                @endif
                            </td>
                            <td>{{ $t->category?->name ?? '—' }}</td>
                            <td class="text-right">{{ number_format((float) $t->amount, 2, ',', ' ') }} €</td>
                            <td class="text-right">
                                <a href="{{ route('finance_transactions.edit', $t) }}" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                <form action="{{ route('finance_transactions.destroy', $t) }}" method="post" style="display:inline;" onsubmit="return confirm('Remover esta transação?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nenhuma transação neste período.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="box-footer clearfix">
                {{ $transactions->links() }}
            </div>
        </div>

        @if(count($totalsByCategory) && empty($filters['category_id']))
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Por categoria (este mês)</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th class="text-right">Receitas</th>
                            <th class="text-right">Despesas</th>
                            <th class="text-right">Líquido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($totalsByCategory as $row)
                        <tr>
                            <td>{{ $row['category_name'] }}</td>
                            <td class="text-right">{{ number_format($row['income'], 2, ',', ' ') }} €</td>
                            <td class="text-right">{{ number_format($row['expense'], 2, ',', ' ') }} €</td>
                            <td class="text-right">{{ number_format($row['net'], 2, ',', ' ') }} €</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
