@extends('cms.admin.layout')

@section('content')
  <form method="get" action="{{ route('cms.admin.users.index') }}" class="mb-3">
    <label for="q">Pesquisar</label>
    <div class="d-flex gap-2" style="gap:0.5rem;">
      <input type="text" id="q" name="q" value="{{ $q }}" class="form-control" placeholder="Nome ou email">
      <button type="submit" class="btn btn-default">Filtrar</button>
    </div>
  </form>

  <table class="table table-bordered table-striped bg-white">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Admin</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @foreach($users as $u)
        <tr>
          <td>{{ $u->name }}</td>
          <td>{{ $u->email }}</td>
          <td>{{ $u->is_admin ? 'Sim' : 'Não' }}</td>
          <td><a href="{{ route('cms.admin.users.edit', $u) }}">Editar</a></td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{ $users->links() }}
@endsection
