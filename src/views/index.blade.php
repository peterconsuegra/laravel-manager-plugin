@extends('layout')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Laravel Manager</h2>
    <a class="btn btn-pete" href="{{ route('lm.create') }}">
      <i class="bi bi-plus-lg"></i> New Laravel
    </a>
  </div>

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="panel">
    <div class="panel-heading"><h3 class="fs-5 mb-0">Laravel instances</h3></div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr>
          <th>ID</th>
          <th>Project</th>
          <th>Action</th>
          <th>Laravel version</th>
          <th>DB name</th>
          <th>URL</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>
          @forelse($sites as $s)
            <tr>
              <td>{{ $s->id }}</td>
              <td>{{ $s->name }}</td>
              <td>{{ $s->action_name }}</td>
              <td>{{ $s->laravel_version }}</td>
              <td>{{ $s->db_name }}</td>
              <td><a href="http://{{ $s->url }}" target="_blank" rel="noopener">{{ $s->url }}</a></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('lm.logs', $s->id) }}">Logs</a>

                <form
                  action="{{ route('lm.delete') }}"
                  method="POST"
                  class="d-inline"
                  onsubmit="return confirm('Delete this Laravel instance? This cannot be undone.');"
                >
                  @csrf
                  <input type="hidden" name="site_id" value="{{ $s->id }}">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </form>

              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted">No integrations yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3">
      {{ $sites->links() }}
    </div>
  </div>
</div>
@endsection
