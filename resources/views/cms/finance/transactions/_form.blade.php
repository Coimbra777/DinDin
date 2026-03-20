@php
    /** @var \App\Modules\Finance\Models\Transaction|null $finance_transaction */
    $t = $finance_transaction ?? null;
@endphp
@include('cms.finance.partials.errors')
<div class="form-group">
    <label for="title">Título *</label>
    <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $t?->title) }}" required maxlength="255">
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="amount">Valor *</label>
            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="{{ old('amount', $t?->amount) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="type">Tipo *</label>
            <select name="type" id="type" class="form-control" required>
                <option value="income" {{ old('type', $t?->type) === 'income' ? 'selected' : '' }}>Receita</option>
                <option value="expense" {{ old('type', $t?->type ?? 'expense') === 'expense' ? 'selected' : '' }}>Despesa</option>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="transaction_date">Data *</label>
            <input type="date" class="form-control" id="transaction_date" name="transaction_date"
                value="{{ old('transaction_date', $t?->transaction_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
        </div>
    </div>
</div>
<div class="form-group">
    <label for="category_id">Categoria</label>
    <select name="category_id" id="category_id" class="form-control">
        <option value="">— Sem categoria —</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->id }}" {{ (string) old('category_id', $t?->category_id) === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label for="description">Descrição</label>
    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $t?->description) }}</textarea>
</div>
