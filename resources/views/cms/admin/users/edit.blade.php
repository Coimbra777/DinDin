@extends('cms.admin.layout')

@section('content')
  <p><a href="{{ route('cms.admin.users.index') }}">← Voltar</a></p>

  <form method="post" action="{{ route('cms.admin.users.update', $user) }}" class="bg-white p-3" style="border:1px solid #ddd;border-radius:4px;">
    @csrf
    @method('PUT')

    <div class="form-group">
      <label for="name">Nome</label>
      <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
    </div>

    <div class="form-group">
      <label>
        <input type="hidden" name="is_admin" value="0">
        <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}>
        Administrador
      </label>
    </div>

    <fieldset class="form-group">
      <legend style="font-size:1rem;border:0;margin-bottom:0.5rem;">Módulos (SaaS)</legend>
      @foreach($modules as $module)
        <div class="checkbox">
          <label>
            <input
              type="checkbox"
              name="saas_module_ids[]"
              value="{{ $module->id }}"
              {{ in_array($module->id, old('saas_module_ids', $user->saasModules->pluck('id')->all()), true) ? 'checked' : '' }}
            >
            {{ $module->name }} <span class="text-muted">({{ $module->slug }})</span>
          </label>
        </div>
      @endforeach
    </fieldset>

    <button type="submit" class="btn btn-primary">Guardar</button>
  </form>
@endsection
