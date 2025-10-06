@extends('layout')

@section('content')
<div class="container-fluid">

  {{-- flash / updater placeholder --}}
    <div id="update_area_info"></div>

    {{-- hero ------------------------------------------------------------- --}}
    <div class="row align-items-center mb-5 g-4">
        <div class="col-md-6 text-center text-md-start">
            <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
        </div>
        <div class="col-md-6 d-flex flex-column justify-content-center">
             <a class="btn btn-pete" href="{{ route('lm.create') }}">
                <i class="bi bi-plus-lg"></i> New Laravel
              </a> 
            <p><br /> Easily create and import Laravel instances into WordPress Pete environment with one click</p>
        </div>
    </div>

    {{-- flash & validation ----------------------------------------------- --}}
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">Please fix the following:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

  <div class="panel">
    <div class="panel-heading d-flex justify-content-between align-items-center">
      <h3 id="laravel_title" class="fs-5 mb-0">Laravel instances</h3>

      <div class="d-flex align-items-center gap-2">
        <form method="GET" action="{{ route('lm.index') }}" class="d-inline-block">
          <label for="per_page" class="form-label me-2 mb-0 small text-muted">Rows per page:</label>
          <select name="per_page" id="per_page"
                  class="form-select form-select-sm d-inline-block w-auto"
                  onchange="this.form.submit()">
            @foreach([5,10,20,50] as $size)
              <option value="{{ $size }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                {{ $size }}
              </option>
            @endforeach
          </select>
        </form>

        <small class="text-muted">{{ number_format($sites->total()) }} total</small>
      </div>
</div>
    <div class="table-responsive">
      <table class="table table-hover table-striped align-middle mb-0">
       <thead>
          <tr>
            <th>ID</th>
            <th>URL</th>
            <th>Info</th>
            <th class="text-center" width="70">SSL</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sites as $s)
            <tr data-site-id="{{ $s->id }}">
              <td>{{ $s->id }}</td>
              <td class="fw-semibold"> <a href="http://{{ $s->url }}" target="_blank" rel="noopener">{{ $s->url }}</a></td>
              <td>
                Action: {{ $s->action_name }} <br />
                Laravel version: {{ $s->laravel_version }} <br />
                DB name: {{ $s->db_name }}
              </td>
              
              <td class="text-center">
                <i class="bi bi-shield-check text-success ssl-indicator" @if(empty($s->ssl)) style="display:none" @endif title="SSL enabled"></i>
                <i class="bi bi-shield-x text-danger ssl-indicator-off" @if(!empty($s->ssl)) style="display:none" @endif title="SSL disabled"></i>
              </td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  {{-- Generate SSL --}}
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-generate-ssl"
                    data-site-id="{{ $s->id }}"
                    title="Generate SSL"
                  >
                    <i class="bi bi-lock-fill me-1"></i><span class="btn-text">Generate SSL</span>
                  </button>

                  {{-- Logs --}}
                  <a class="btn btn-sm btn-info" href="{{ route('lm.logs', $s->id) }}">
                    <i class="bi bi-journal-text"></i> Logs
                  </a>

                  {{-- Delete --}}
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
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-muted">No integrations yet.</td></tr>
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

@push('scripts')
<script>
(function () {
  const generateSslUrl = @json(route('lm.generate-ssl', [], false));
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  function setBusy(btn, busy) {
    if (!btn) return;
    const icon = btn.querySelector('i.bi');
    const text = btn.querySelector('.btn-text');
    btn.disabled = !!busy;

    if (busy) {
      btn.dataset.prevHtml = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Generating…';
    } else if (btn.dataset.prevHtml) {
      btn.innerHTML = btn.dataset.prevHtml;
      delete btn.dataset.prevHtml;
    }
  }

  async function handleGenerateSslClick(e) {
    const btn = e.currentTarget;
    const siteId = Number(btn?.dataset?.siteId || 0);
    if (!siteId) return;

    if (!confirm('Generate a new SSL certificate for this Laravel instance?')) return;

    setBusy(btn, true);
    try {
      const res = await fetch(generateSslUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify({ site_id: siteId })
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok || data.error) {
        window.toast?.(data.message || 'SSL generation failed.', 'error');
        return;
      }

      // Optimistic UI: flip the shield icons in this row
      const row = btn.closest('tr[data-site-id]');
      if (row) {
        const onIcon  = row.querySelector('.ssl-indicator');
        const offIcon = row.querySelector('.ssl-indicator-off');
        if (onIcon)  onIcon.style.display  = '';
        if (offIcon) offIcon.style.display = 'none';
      }

      window.toast?.('SSL generation started.', 'success');
    } catch (err) {
      window.toast?.('Network error. Please try again.', 'error');
    } finally {
      setBusy(btn, false);
    }
  }

  function wireButtons() {
    document.querySelectorAll('.btn-generate-ssl').forEach(btn => {
      btn.removeEventListener('click', handleGenerateSslClick);
      btn.addEventListener('click', handleGenerateSslClick);
    });
  }

  // Initial bind
  wireButtons();
})();
</script>
@endpush