@extends('layout')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Laravel Manager</h2>
    <a class="btn btn-pete" href="{{ route('lm.create') }}">
      <i class="bi bi-plus-lg"></i> New Integration
    </a>
  </div>

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="panel">
    <div class="panel-heading"><h3 class="fs-5 mb-0">Integrations</h3></div>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr>
          <th>ID</th>
          <th>Project</th>
          <th>Type</th>
          <th>URL</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>
          @forelse($sites as $s)
            @php
              $integrationUrl = $s->integration_type === 'separate_subdomain'
                ? $s->wordpress_laravel_url
                : ($s->wp_url . '/' . $s->name);
            @endphp
            <tr>
              <td>{{ $s->id }}</td>
              <td>{{ $s->name }}</td>
              <td>{{ $s->integration_type === 'inside_wordpress' ? 'Same Domain' : 'Subdomain' }}</td>
              <td><a href="http://{{ $integrationUrl }}" target="_blank" rel="noopener">{{ $integrationUrl }}</a></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('lm.logs', $s->id) }}">Logs</a>
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
