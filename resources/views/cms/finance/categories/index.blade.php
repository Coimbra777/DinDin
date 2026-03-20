@extends('cms.layouts.page')

@section('content')
<div class="row">
    <div class="col-md-12">
        @include('cms.finance.partials.flash-success')

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Categorias</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('finance_categories.create') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Nova categoria
                    </a>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Cor</th>
                            <th class="text-right">Receitas</th>
                            <th class="text-right">Despesas</th>
                            <th class="text-right">Líquido</th>
                            <th class="text-right" width="120">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalsKeyed = [];
                            foreach ($totalsByCategory as $k => $row) {
                                if ($k === 0) continue;
                                $totalsKeyed[$k] = $row;
                            }
                        @endphp
                        @forelse($categories as $c)
                        @php $agg = $totalsKeyed[$c->id] ?? ['income' => 0, 'expense' => 0, 'net' => 0]; @endphp
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td>
                                @if($c->color)
                                <span class="label" style="background:{{ $c->color }};">&nbsp;</span>
                                {{ $c->color }}
                                @else
                                —
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($agg['income'], 2, ',', ' ') }} €</td>
                            <td class="text-right">{{ number_format($agg['expense'], 2, ',', ' ') }} €</td>
                            <td class="text-right">{{ number_format($agg['net'], 2, ',', ' ') }} €</td>
                            <td class="text-right">
                                <a href="{{ route('finance_categories.edit', $c) }}" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                <form action="{{ route('finance_categories.destroy', $c) }}" method="post" style="display:inline;" onsubmit="return confirm('Remover esta categoria?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Ainda não existem categorias.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="box-footer clearfix">
                {{ $categories->links() }}
            </div>
        </div>

        @if(isset($totalsByCategory[0]))
        <p class="text-muted">
            <strong>Transações sem categoria:</strong>
            Receitas {{ number_format($totalsByCategory[0]['income'], 2, ',', ' ') }} € —
            Despesas {{ number_format($totalsByCategory[0]['expense'], 2, ',', ' ') }} €
        </p>
        @endif
    </div>
</div>
@endsection
