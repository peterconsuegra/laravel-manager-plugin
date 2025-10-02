<?php

namespace Pete\LaravelManager\Http;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Pete\WordPressPlusLaravel\Models\Site;       // Reuse your extended Site model if desired
use App\Services\PeteOption;                      // Same PeteOption service used in WPL
use Symfony\Component\HttpFoundation\Response;
use App\Services\PeteService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LaravelManagerController extends Controller
{
    private PeteService $pete;
    private PeteOption $peteOptions;

    public function __construct(PeteService $pete, PeteOption $peteOptions)
    {
        $this->middleware('auth');
        $this->pete = $pete;
		$this->peteOptions = $peteOptions;
    }

    public function index()
    {
        // List the Laravel syncs/sites relevant to this manager
        // Adjust the query to your own structure
        $sites = Site::where('app_name', 'Laravel')
            ->orderByDesc('id')
            ->paginate(20);
        
        $viewsw      = 'laravel-manager';

        return view('laravel-manager::index', compact('sites','viewsw'));
    }

    public function create(Request $request)
    {
        // SSH key (optional): show to user for private repos
        [$sshPubKey, $sshKeyError] = $this->readSshPublicKey();

         $viewsw      = 'laravel-manager';

        return view('laravel-manager::create', [
            'sshPubKey'    => $sshPubKey,
            'sshKeyError'  => $sshKeyError,
            'viewsw' => $viewsw
        ]);
    }

    public function store(Request $request)
    {
        // 0) Normalize URL with domain template (same approach as SiteController)
        $tpl     = (string) $this->peteOptions->get_meta_value('domain_template');
        $visual  = (string) $request->input('laravel_url_visual', '');
        $fullUrl = $this->pete->normalizeUrlWithTemplate($visual, $tpl);

        // Sanitize optional inputs so they don't trip type checks
        $gitUrl    = $request->has('laravel_git') ? $request->input('laravel_git') : null;
        $gitBranch = $request->has('laravel_git_branch') ? $request->input('laravel_git_branch') : null;

        // Build the bag we actually validate/persist
        $input = array_replace($request->all(), [
            'url'                 => $fullUrl,
            'laravel_git'         => $gitUrl ?: null,
            'laravel_git_branch'  => $gitBranch ?: null,
        ]);

        
        $v = Validator::make($input, [
            'action_name' => ['required', 'in:new_laravel,import_laravel'],

            // ✅ include this
            'selected_version' => [
                Rule::requiredIf(fn () => ($input['action_name'] ?? '') === 'new_laravel'),
                'nullable',
                'string',
                // Optionally constrain allowed values:
                Rule::in(['10.*','11.*','12.*']),
                // or allow anything like "10.*": 'regex:/^\d+\.\*$/'
            ],

            // Git fields (only for import)
            'laravel_git' => [
                Rule::requiredIf(fn () => ($input['action_name'] ?? '') === 'import_laravel'),
                'nullable', 'string',
            ],
            'laravel_git_branch' => [
                Rule::requiredIf(fn () => ($input['action_name'] ?? '') === 'import_laravel'),
                'nullable', 'string',
            ],

            // URL (same as before)
            'url' => [
                'required',
                'max:255',
                'regex:/^(?!\-)(?:[a-z0-9\-]+\.)+[a-z]{2,}$/i',
                Rule::unique('sites', 'url'),
            ],
        ], [
            'url.regex' => 'Please enter a valid domain, e.g. myapp.example.com or sub.mysite.org',
            'laravel_git.required'        => 'Repository URL is required for Import.',
            'laravel_git_branch.required' => 'Branch is required for Import.',
            'selected_version.required'   => 'Please select a Laravel version.',
        ]);

        if ($v->fails()) {
            return $this->pete->fail($request, 'Validation failed.', $v->errors()->toArray(), 422);
        }

        // 2) Business guard: forbidden domains
        if ($this->pete->isTheURLForbidden($fullUrl)) {
            return $this->pete->fail($request, 'URL forbidden.', [
                'laravel_url_visual' => ['This URL is not allowed.'],
            ], 422);
        }

        // 3) Persist
        $data = $v->validated();

        $site = new Site();
        $site->action_name                  = $data['action_name'] === 'new_laravel' ? 'New' : 'Import';
        $site->url                          = $fullUrl;         // normalized & validated
        $site->set_project_name($site->url);
        $site->app_name                     = 'Laravel';
        $site->wordpress_laravel_git        = $data['laravel_git']        ?? null;
        $site->wordpress_laravel_git_branch = $data['laravel_git_branch'] ?? null;
        $site->laravel_version              = $data['selected_version']   ?? null;

        $site->save();
        // $site->create_laravel();

        // 4) Respond
        return response()->json([
            'ok'  => true,
            'id'  => $site->id,
            'url' => route('lm.logs', $site->id),
        ], Response::HTTP_CREATED);
    }



    public function logs(int $id)
    {
        $site = Site::findOrFail($id);

        // server logs (optional), pass paths/content like you do now
        $web_server_error_file         = '/var/log/apache2/error.log';
        $web_server_access_file        = '/var/log/apache2/access.log';
        $web_server_error_file_content = @file_get_contents($web_server_error_file) ?: '';
        $web_server_access_file_content= @file_get_contents($web_server_access_file) ?: '';

        $viewsw      = 'laravel-manager';

        return view('laravel-manager::logs', compact(
            'site',
            'web_server_error_file',
            'web_server_access_file',
            'web_server_error_file_content',
            'web_server_access_file_content',
            'viewsw'
        ));
    }

    public function delete(Request $request)
    {
        $data = $request->validate([
            'site_id' => ['required', 'integer', 'exists:sites,id'],
        ]);

        $site = Site::findOrFail((int) $data['site_id']);

        try {
            // If you want to run your removal script too, uncomment:
            // $site->delete_wordpress_laravel();

            $site->delete(); // <- remove DB row

            return back()->with('status', 'Laravel instance removed.');
        } catch (\Throwable $e) {
            \Log::error('LaravelManager delete failed', [
                'site_id' => $site->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors('Delete failed. Please try again.');
        }
    }

    public function generateSsl(Request $request)
    {
        // Wire this to your existing internal SSL endpoint/CGI if desired
        // or return a JSON message while you pipe to your CGI script.
        return response()->json(['ok' => true, 'message' => 'SSL generation queued']);
    }

    private function readSshPublicKey(): array
    {
        $script = base_path('scripts/get_ssh_pub_key.sh');

        if (!is_file($script)) {
            return ['', "Script not found: {$script}"];
        }

        // You can make the script executable in the image/container, but we also
        // support invoking through 'sh' so it works even if not +x.
        $cmd = 'sh ' . escapeshellarg($script) . ' 2>&1';

        $outputLines = [];
        $code        = 0;
        exec($cmd, $outputLines, $code);

        if ($code !== 0) {
            $msg = trim(implode("\n", $outputLines)) ?: 'Unable to read SSH public key.';
            return ['', $msg];
        }

        $pub = trim(implode("\n", $outputLines));
        if ($pub === '') {
            return ['', 'SSH public key appears empty.'];
        }

        return [$pub, null];
    }
}
