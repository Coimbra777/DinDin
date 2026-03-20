@php
    /** @var \App\Modules\Finance\Models\Category|null $finance_category */
    $c = $finance_category ?? null;
@endphp
@include('cms.finance.partials.errors')
<div class="form-group">
    <label for="name">Nome *</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $c?->name) }}" required maxlength="120">
</div>
<div class="form-group">
    <label for="color">Cor (hex, opcional)</label>
    <input type="text" class="form-control" id="color" name="color" value="{{ old('color', $c?->color) }}" placeholder="#2563eb" maxlength="7">
</div>
