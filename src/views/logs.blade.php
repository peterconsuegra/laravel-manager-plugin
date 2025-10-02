@extends('layout')

@push('head')
<style>
  /* Bounded, scrollable container for big logs */
  .log-box{
    max-width: 1024px !important;
    max-height: 50vh !important;
    overflow-y: auto !important;
    overflow-x: auto;
    background: #0b1020;
    color: #e6e6e6;
    border-radius: .375rem;
    border: 1px solid rgba(0,0,0,.08);
    display: block;
  }

  .panel{
    max-width: 1024px !important;
  }

  .terminal-output{
    margin: 0;
    padding: 1rem;
    white-space: pre-wrap;
    word-break: break-word;
    overflow-wrap: anywhere;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    line-height: 1.45;
    font-size: .875rem;
    color: #fff;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- hero ------------------------------------------------------------- --}}
  <div class="row align-items-center mb-4 g-3">
    <div class="col-md-5">
      <h2 class="mb-0">Laravel Manager — Logs</h2>
    </div>


    <div class="col-md-7 d-flex gap-2 justify-content-md-end">
      <a href="{{ route('lm.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to list
      </a>
     
     
    </div>
  </div>

  @if($site->action_name == "New")
  {{-- details (New) ---------------------------------------------------- --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading">
          <h3 class="mb-0 fs-5">Integration Details</h3>
        </div>
        <div class="p-3">
          <div class="row gy-2 small">
            <div class="col-md-6">
              <div class="text-uppercase text-muted">Laravel URL</div>
              <div>
                <a href="http://{{ $site->url }}" target="_blank" rel="noopener">
                  {{ $site->url }}
                </a>
              </div>
            </div>
            <div class="col-md-6">
              
            </div>
            <div class="col-md-6">
              <div class="text-uppercase text-muted">Laravel Version</div>
              <div>{{ $site->laravel_version ?? '—' }}</div>
            </div>
            <div class="col-md-6">
              <div class="text-uppercase text-muted">Project Name</div>
              <div>{{ $site->name }}</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
  @else
  {{-- details (Import) ------------------------------------------------- --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading">
          <h3 class="mb-0 fs-5">Integration Details</h3>
        </div>
        <div class="p-3">
          <div class="row gy-2 small">
            <div class="col-md-6">
              <div class="text-uppercase text-muted">Laravel URL</div>
              <div>
                <strong>
                  <a href="http://{{ $site->url }}" target="_blank" rel="noopener">{{ $site->url }}</a>
                </strong>
              </div>
            </div>
            <div class="col-md-6">
              <div class="text-uppercase text-muted">Sync Type</div>
              <div>{{ $site->integration_type === 'inside_wordpress' ? 'Same Domain' : 'Separate Subdomain' }}</div>
            </div>
            <div class="col-md-6">
              <div class="text-uppercase text-muted">Branch</div>
              <div>{{ $site->wordpress_laravel_git_branch ?? '—' }}</div>
            </div>
            <div class="col-md-6">
              <div class="text-uppercase text-muted">GIT URL</div>
              <div>{{ $site->wordpress_laravel_git }}</div>
            </div>
            <div class="col-md-6">
              <div class="text-uppercase text-muted">Project</div>
              <div>{{ $site->name }}</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
  @endif

  @php
    // Strip ANSI color codes from output
    $clean = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $site->output ?? '');
  @endphp

  {{-- terminal output --------------------------------------------------- --}}
  <div class="row g-4">
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-6"><i class="bi bi-terminal me-1"></i> Terminal output</h3>
        </div>

        <div class="log-box" data-autoscroll="end">
          <pre class="terminal-output">{{ $clean }}</pre>
        </div>
      </div>
    </div>

    {{-- Apache error.log ------------------------------------------------- --}}
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-6"><i class="bi bi-exclamation-triangle me-1"></i> Apache error.log</h3>
          <small class="text-muted">{{ $web_server_error_file }}</small>
        </div>
        <pre class="mb-0 small p-3 bg-light" style="max-height:420px; overflow:auto">{{ $web_server_error_file_content }}</pre>
      </div>
    </div>

    {{-- Apache access.log ------------------------------------------------ --}}
    <div class="col-12">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-6"><i class="bi bi-list-check me-1"></i> Apache access.log</h3>
          <small class="text-muted">{{ $web_server_access_file }}</small>
        </div>
        <pre class="mb-0 small p-3 bg-light" style="max-height:420px; overflow:auto">{{ $web_server_access_file_content }}</pre>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  // Auto-scroll any container marked with data-autoscroll="end"
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-autoscroll="end"]').forEach(function (el) {
      el.scrollTop = el.scrollHeight;
    });
  });
</script>
@endpush
