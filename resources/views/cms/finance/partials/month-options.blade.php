{{-- Uso: @include(..., ['monthChoices' => $monthChoices, 'selected' => $month]) --}}
@foreach($monthChoices as $value => $label)
<option value="{{ $value }}" {{ ($selected ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
@endforeach
