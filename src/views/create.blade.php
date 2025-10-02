@extends('layout')

@push('head')
<style>
  /* Spinner via pseudo-element; never touches the button label */
  #create_button[data-loading="1"]{ position:relative; }
  #create_button[data-loading="1"]::after{
    content:"";
    display:inline-block;
    width:.9rem;height:.9rem;margin-left:.5rem;
    border:.15rem solid currentColor;border-right-color:transparent;
    border-radius:50%;
    vertical-align:-2px;
    animation:pete-spin .6s linear infinite;
  }
  @keyframes pete-spin { to { transform: rotate(360deg); } }

  /* Pete button disabled state */
  .btn-pete:disabled,
  .btn-pete[disabled]{
    opacity:1 !important;
    cursor:not-allowed;
    color:#fff !important;
    background-image:linear-gradient(135deg,var(--pete-blue) 0%,var(--pete-green) 100%) !important;
    box-shadow:0 3px 8px rgba(0,0,0,.2) !important;
    pointer-events:none;
  }
  .btn-pete:disabled:hover,
  .btn-pete:disabled:focus,
  .btn-pete:disabled:active,
  .btn-pete[disabled]:hover,
  .btn-pete[disabled]:focus,
  .btn-pete[disabled]:active{
    background-image:linear-gradient(135deg,var(--pete-blue) 0%,var(--pete-green) 100%) !important;
    color:#fff !important;
    box-shadow:0 3px 8px rgba(0,0,0,.2) !important;
    transform:none !important;
    text-decoration:none !important;
  }

  /* Skeleton styles */
  .skel-row{
    height:14px;border-radius:6px;
    background:linear-gradient(90deg,#e9ecef 25%,#f8f9fa 37%,#e9ecef 63%);
    background-size:400% 100%;
    animation:skel 1.2s ease-in-out infinite;
  }
  @keyframes skel{ 0%{background-position:100% 0} 100%{background-position:0 0} }
  .step-dot{ width:.75rem;height:.75rem;border-radius:50%;background-color:#ced4da;display:inline-block; }
  .step.active .step-dot{ background-color:#0d6efd; }
  .step-label{ font-size:.9rem }
  [v-cloak]{ display:none; }
</style>
@endpush

@section('content')
@php
  // Use the same "domain template" approach as resources/views/sites/create.blade.php
  $template = $template ?? (isset($pete_options) ? ($pete_options->get_meta_value('domain_template') ?? null) : null);
@endphp

<div id="lm-create" class="container-fluid" v-cloak>

  {{-- hero ------------------------------------------------------------- --}}
  <div class="row align-items-center mb-5 g-4">
    <div class="col-md-6 text-center text-md-start">
      <img src="/pete.png" alt="WordPress Pete" class="img-fluid" style="max-height:200px">
    </div>
    <div class="col-md-6 d-flex flex-column justify-content-center">
      <h2 class="mb-1">Create or Import a Laravel Instance</h2>
      <p>Scale smarter: run Laravel inside WordPress Pete’s fast and reliable ecosystem.</p>
    </div>
  </div>

  {{-- SSH Public Key --------------------------------------------------------- --}}
  <div class="row mb-4">
    <div class="col-lg-9 col-xl-8">
      <div class="panel">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-5">Server SSH Public Key</h3>
        </div>
        <div class="p-3 p-md-4">
          @if(!empty($sshKeyError))
            <div class="alert alert-warning small mb-0">
              <strong>Couldn’t read the public key.</strong><br>
              <span class="text-muted">{{ $sshKeyError }}</span>
            </div>
          @else
            <label for="sshPubKey" class="form-label">Use this read-only key for deploy/read-only access</label>
            <div class="input-group">
              <textarea id="sshPubKey"
                        class="form-control"
                        rows="3"
                        readonly
                        spellcheck="false">{{ $sshPubKey }}</textarea>
              <button class="btn btn-outline-secondary"
                      type="button"
                      id="btnCopyKey"
                      title="Copy to clipboard">
                <i class="bi bi-clipboard" aria-hidden="true"></i>
                <span class="visually-hidden">Copy</span>
              </button>
            </div>
            <div class="form-text">
              This is the <em>public</em> key (<code>/var/www/.ssh/id_ed25519.pub</code>); it’s safe to share with Git providers.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- flash & validation ----------------------------------------------- --}}
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  {{-- Vue-driven error block --}}
  <div v-if="Object.keys(errors).length" class="alert alert-danger">
    <div class="fw-semibold mb-2">Please fix the following:</div>
    <ul class="mb-0">
      <li v-for="(msgs, field) in errors" :key="field">
        <span v-for="(m, i) in [].concat(msgs)" :key="i">@{{ m }}</span>
      </li>
    </ul>
  </div>

  {{-- form card --------------------------------------------------------- --}}
  <div class="row">
    <div class="col-lg-9 col-xl-8">
      <div class="panel position-relative">
        <div class="panel-heading d-flex justify-content-between align-items-center">
          <h3 class="mb-0 fs-5">Integration details</h3>
          <a href="{{ route('lm.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to list
          </a>
        </div>

        <form
          action="{{ route('lm.store') }}"
          id="LmForm"
          method="POST"
          class="p-3 p-md-4"
          novalidate
          @submit.prevent="submit"
        >
          @csrf

          {{-- Action (New or Import) --}}
          <div class="mb-3">
            <label for="action_name-field" class="form-label">Action</label>
            <select class="form-select" id="action_name-field" name="action_name" v-model="form.action_name" required>
              <option value="">Select Action</option>
              <option value="new_laravel">New</option>
              <option value="import_laravel">Import</option>
            </select>
          </div>

          {{-- Laravel version (for New or Import baseline) --}}
          <div class="mb-3" id="selected_version_div" v-show="showVersion">
            <label for="selected_version" class="form-label">Laravel version</label>
            <select class="form-select" id="selected_version" name="selected_version" v-model="form.selected_version">
              <option value="">Select Laravel version</option>
              <option value="8.*">8.*</option>
              <option value="9.*">9.*</option>
              <option value="10.*">10.*</option>
              <option value="11.*">11.*</option>
              <option value="12.*">12.*</option>
            </select>
          </div>

          {{-- Import from Git (only for Import) --}}
          <div id="import_git_block" class="mb-3" v-show="showImport">
            <div class="alert alert-warning small">
              <strong>Private repo?</strong>
              Copy the SSH public key above into your Git profile, then use the SSH clone URL (starts with git@…, e.g. git@github.com:username/repo.git). Public repos can use HTTPS.
            </div>

            <div class="alert alert-secondary small mt-2">
              After the first import, continue deployments from inside the PHP container: open the container, switch to your app folder, and pull the desired branch or tag (e.g., main, release-2025-09, v1.3.0).
            </div>

            <div class="row g-3">
              <div class="col-md-8">
                <label for="laravel_git-field" class="form-label">Repository URL</label>
                <input type="text"
                       id="laravel_git-field"
                       name="laravel_git"
                       v-model="form.laravel_git"
                       :class="['form-control', gitUrlClass]"
                       placeholder="https://github.com/user/project.git">
              </div>
              <div class="col-md-4">
                <label for="laravel_git_branch-field" class="form-label">Branch</label>
                <input type="text"
                       id="laravel_git_branch-field"
                       name="laravel_git_branch"
                       v-model="form.laravel_git_branch"
                       class="form-control"
                       placeholder="main">
              </div>
            </div>
          </div>

          {{-- Laravel URL (same UX as sites/create) --}}
          <div class="mb-3" id="app_name_wrap" v-show="showCommon">
            <label for="laravel-url-field" class="form-label">Laravel URL</label>

            @if($template && $template !== 'none')
              <div class="input-group">
                <input
                  type="text"
                  class="form-control"
                  id="laravel-url-field"
                  name="laravel_url_visual"
                  placeholder="subdomain"
                  v-model.trim="urlInput"
                  required
                  aria-describedby="laravelUrlHelp"
                >
                <span class="input-group-text">.{{ $template }}</span>
              </div>
              <div id="laravelUrlHelp" class="form-text">
                Enter only the subdomain; Pete appends <code>.{{ $template }}</code> automatically.
              </div>
            @else
              <input
                type="text"
                class="form-control"
                id="laravel-url-field"
                name="laravel_url_visual"
                placeholder="e.g. myapp.mysite.com or mysite.com/myapp"
                v-model.trim="urlInput"
                required
                aria-describedby="laravelUrlHelp"
              >
              <div id="laravelUrlHelp" class="form-text">
                Use a valid host/domain (don’t include protocol). For path installs, use <code>example.com/myapp</code>.
              </div>
            @endif

            <div class="form-text">@{{ appNameHint }}</div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button type="submit"
                    id="create_button"
                    class="btn btn-pete d-inline-flex align-items-center gap-2"
                    :disabled="submitting"
                    :data-loading="submitting ? 1 : null">
              <i class="bi bi-plus-lg" aria-hidden="true"></i>
              <span class="btn-text" v-text="submitting ? 'Creating…' : 'Create'"></span>
            </button>
            <a href="{{ route('lm.index') }}" class="btn btn-outline-secondary">Cancel</a>

            <span id="creating_hint"
                  class="ms-2 text-muted small"
                  aria-live="polite"
                  v-show="submitting">
              <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
              Provisioning…
            </span>
          </div>
        </form>

        {{-- Provisioning steps (skeleton) -------------------------------- --}}
        <div id="provision_skeleton" class="border-top px-3 px-md-4 py-3" v-show="submitting">
          <div class="d-flex align-items-center gap-3 mb-2">
            <strong class="me-1">Setting things up…</strong>
            <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
          </div>

          <div class="vstack gap-2">
            <div class="d-flex align-items-center gap-2 step active">
              <span class="step-dot"></span>
              <div class="step-label">Creating Laravel app / files</div>
            </div>
            <div class="skel-row" style="width:55%"></div>

            <div class="d-flex align-items-center gap-2 step">
              <span class="step-dot"></span>
              <div class="step-label">Installing dependencies</div>
            </div>
            <div class="skel-row" style="width:62%"></div>

            <div class="d-flex align-items-center gap-2 step">
              <span class="step-dot"></span>
              <div class="step-label">Reloading web server</div>
            </div>
            <div class="skel-row" style="width:48%"></div>
          </div>
        </div>

        <div class="panel-footer small text-muted">
          WordPress Pete will provision the Laravel integration and reload the web server automatically.
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
{{-- Vue 3 (only if not already loaded by layout) --}}
<script>
  (function ensureVue(){
    if(!window.Vue){
      var s=document.createElement('script');
      s.src='https://unpkg.com/vue@3/dist/vue.global.prod.js';
      document.head.appendChild(s);
    }
  })();
</script>

<script>
(function initVue(){
  function boot(){
    const { createApp, computed, onMounted, reactive, ref, watch } = Vue;

    createApp({
      setup(){
        const submitting   = ref(false);
        const sites        = ref([]);
        const sitesLoading = ref(false);
        const errors       = reactive({});

        // URL input similar to sites/create
        const urlInput = ref('');

        // old() hydration from Blade
        const oldVals = {
          action_name:        @json(old('action_name')),
          selected_version:   @json(old('selected_version')),
          laravel_git:        @json(old('laravel_git')),
          laravel_git_branch: @json(old('laravel_git_branch')),
          laravel_url_visual: @json(old('laravel_url_visual')),
        };

        const form = reactive({
          action_name:        oldVals.action_name || '',
          selected_version:   oldVals.selected_version || '',
          laravel_git:        oldVals.laravel_git || '',
          laravel_git_branch: oldVals.laravel_git_branch || 'main',
          laravel_url_visual: oldVals.laravel_url_visual || '',
        });

        const integrationHelp = ref('');
        const appNameHint     = ref('');

        const showCommon  = computed(()=> !!form.action_name);
        const showVersion = computed(()=> form.action_name === 'new_laravel' || form.action_name === 'import_laravel');
        const showImport  = computed(()=> form.action_name === 'import_laravel');

        const gitUrlOk = computed(()=>{
          const v = (form.laravel_git || '').trim();
          if(!v) return true; // neutral until user types
          return v.startsWith('https://') || v.startsWith('git@');
        });
        const gitUrlClass = computed(()=> gitUrlOk.value ? 'is-valid' : 'is-invalid');

        function updateHints(){
          if(form.integration_type === 'inside_wordpress'){
            integrationHelp.value = 'Laravel will live inside the same domain. Example URL: mywordpresssite.com/myapp';
            appNameHint.value     = 'This becomes the path segment (e.g., /myapp).';
          }else if(form.integration_type === 'separate_subdomain'){
            integrationHelp.value = 'Laravel will run on a subdomain. Example URL: myapp.mywordpresssite.com';
            appNameHint.value     = 'This becomes the subdomain (e.g., myapp.*).';
          }else{
            integrationHelp.value = '';
            appNameHint.value     = '';
          }
        }

        function normalizeHost(val){
          return (val || '')
            .trim()
            .replace(/^https?:\/\//i, '')
            .replace(/\/+$/,'');
        }

        async function loadWordPressSites(){
          sitesLoading.value = true;
          try{
            const tokenEl = document.querySelector('#LmForm input[name=_token]');
            const token   = tokenEl ? tokenEl.value : '';
            const res = await fetch('{{ route('sites.get.sites') }}', {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
              },
              body: JSON.stringify({ app_name: 'Wordpress+laravel' })
            });
            const data = await res.json();
            sites.value = Array.isArray(data) ? data : [];
          }catch(e){
            console.error('Failed to load sites', e);
            sites.value = [];
          }finally{
            sitesLoading.value = false;
          }
        }

        // Async submit
        async function submit(){
          // clear previous errors
          for (const k of Object.keys(errors)) delete errors[k];

          // Map visible URL field -> backend field name expected by controller
          form.laravel_url_visual = normalizeHost(urlInput.value); // <-- fixed line

          // basic client checks (mirrors backend + URL required)
          if(!form.action_name){
            errors.action_name = ['Please select an action.'];
          }
          if(form.action_name === 'new_laravel'){
            if(!form.selected_version) errors.selected_version = ['Please select a Laravel version.'];
          }
          if(form.action_name === 'import_laravel'){
            if(!form.laravel_git) errors.laravel_git = ['Repository URL is required.'];
            if(!gitUrlOk.value)   errors.laravel_git = ['Use an https:// or git@ URL.'];
            if(!form.laravel_git_branch) errors.laravel_git_branch = ['Branch is required.'];
          }

          if(!form.laravel_url_visual){
            errors.laravel_url_visual = ['Please enter a Laravel URL/subdomain.'];
          }

          if(Object.keys(errors).length){ return; }

          submitting.value = true;
          try{
            const token = document.querySelector('#LmForm input[name=_token]')?.value || '';
            const res = await fetch(@json(route('lm.store')), {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
              },
              body: JSON.stringify({ ...form })
            });

            if (res.status === 422){
              const data = await res.json().catch(()=>({}));
              Object.assign(errors, data.errors || { form: [data.message || 'Validation failed.'] });
              submitting.value = false;
              return;
            }

            if (!res.ok){
              let msg = 'Failed to create integration. Please try again.';
              try{
                const data = await res.json();
                if (data && data.message) msg = data.message;
              }catch(_){}
              window.toast && window.toast(msg, 'error');
              submitting.value = false;
              return;
            }

            // On success, go back to index (from there user can open logs)
            window.location.assign(@json(route('lm.index')));
          }catch(err){
            console.error(err);
            window.toast && window.toast('Unexpected error. Please try again.', 'error');
            submitting.value = false;
          }
        }

        // React when user picks an action
        watch(()=> form.action_name, (val)=>{
          if(val){ loadWordPressSites(); }
          updateHints();
        });

        watch(()=> form.integration_type, updateHints);

        onMounted(()=>{
          if(form.action_name){ loadWordPressSites(); }
          updateHints();

          // If old('laravel_url_visual') existed, hydrate visible url field
          if(form.laravel_url_visual && !urlInput.value){
             urlInput.value = form.laravel_url_visual;
          }

          // Focus the URL input shortly after mount
          setTimeout(() => {
            try { document.getElementById('laravel-url-field')?.focus(); } catch(_) {}
          }, 80);

          // Reset UI if page is restored from bfcache
          window.addEventListener('pageshow', function (evt) {
            if (evt.persisted) {
              submitting.value = false;
            }
          });
        });

        return {
          form,
          urlInput,
          submitting,
          sites,
          sitesLoading,
          errors,
          showCommon: computed(()=> showCommon.value),
          showVersion,
          showImport,
          gitUrlClass,
          integrationHelp,
          appNameHint,
          updateHints,
          submit,
        };
      }
    }).mount('#lm-create');
  }

  if(window.Vue) boot();
  else {
    const iv = setInterval(()=>{ if(window.Vue){ clearInterval(iv); boot(); } }, 25);
    setTimeout(()=> clearInterval(iv), 4000);
  }
})();
</script>

<script>
  (function(){
    document.addEventListener('click', function(e){
      const btn = e.target.closest('#btnCopyKey');
      if(!btn) return;

      const ta = document.getElementById('sshPubKey');
      if(!ta) return;

      const text = ta.value;

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function(){
          btn.innerHTML = '<i class="bi bi-clipboard-check"></i>';
          setTimeout(()=> btn.innerHTML = '<i class="bi bi-clipboard"></i>', 1500);
        }).catch(function(){
          copyFallback(ta, btn);
        });
      } else {
        copyFallback(ta, btn);
      }

      function copyFallback(el, button){
        el.focus();
        el.select();
        el.setSelectionRange(0, el.value.length);
        try {
          const ok = document.execCommand('copy');
          if (ok) {
            button.innerHTML = '<i class="bi bi-clipboard-check"></i>';
            setTimeout(()=> button.innerHTML = '<i class="bi bi-clipboard"></i>', 1500);
          }
        } catch(_) {}
      }
    }, false);
  })();
</script>
@endpush
