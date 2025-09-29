<?php

namespace Pete\LaravelManager\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Pete\WordPressPlusLaravel\Models\Site;       // Reuse your extended Site model if desired
use App\Services\PeteOption;                      // Same PeteOption service used in WPL
use Symfony\Component\HttpFoundation\Response;

class LaravelManagerController extends Controller
{
    public function index()
    {
        // List the Laravel syncs/sites relevant to this manager
        // Adjust the query to your own structure
        $sites = Site::where('app_name', 'WordPress+Laravel')
            ->orderByDesc('id')
            ->paginate(20);

        return view('laravel-manager::index', compact('sites'));
    }

    public function create(Request $request)
    {
        // SSH key (optional): show to user for private repos
        [$sshPubKey, $sshKeyError] = $this->readSshPublicKey();

        return view('laravel-manager::create', [
            'sshPubKey'    => $sshPubKey,
            'sshKeyError'  => $sshKeyError,
        ]);
    }

    public function store(Request $request)
    {
        // Validate similar to your existing Vue client checks
        $v = $request->validate([
            'action_name'               => 'required|in:new_laravel,import_laravel',
            'selected_version'          => 'nullable|string',
            'integration_type'          => 'required|in:inside_wordpress,separate_subdomain',
            'laravel_git'               => 'nullable|string',
            'laravel_git_branch'        => 'nullable|string',
            'laravel_app_name'          => 'required|string',
            'laravel_target'            => 'required|integer', // WordPress site id
        ]);

        $debug = env('PETE_DEBUG');
        $options = new PeteOption();

        $target = Site::findOrFail((int) $v['laravel_target']);
        $dbPass = Crypt::decryptString($target->db_password);

        // Build the new Site row representing this integration
        $site = new Site();
        $site->action_name = $v['action_name'] === 'new_laravel' ? 'New' : 'Import';
        $site->name = $v['laravel_app_name'];
        $site->wordpress_laravel_target_id = $target->id;
        $site->integration_type = $v['integration_type'];
        $site->wordpress_laravel_git = $v['laravel_git'] ?? null;
        $site->wordpress_laravel_git_branch = $v['laravel_git_branch'] ?? null;
        $site->laravel_version = $v['selected_version'] ?? null;

        // Reuse your Site::create_wordpress_laravel() method pipeline
        // (It already creates the URL, runs scripts, and appends logs into $site->output)
        $site->save();
        $site->create_wordpress_laravel();

        // After provisioning, redirect to logs
        return response()->json([
            'ok'  => true,
            'id'  => $site->id,
            'url' => route('lm.logs', $site->id),
        ], Response::HTTP_CREATED);
    }

    public function logs(int $id)
    {
        $site = Site::findOrFail($id);

        // Locate target WordPress site for URL composition (same as your logs view)
        $target = Site::findOrFail($site->wordpress_laravel_target_id ?? $site->id);

        // server logs (optional), pass paths/content like you do now
        $web_server_error_file         = '/var/log/apache2/error.log';
        $web_server_access_file        = '/var/log/apache2/access.log';
        $web_server_error_file_content = @file_get_contents($web_server_error_file) ?: '';
        $web_server_access_file_content= @file_get_contents($web_server_access_file) ?: '';

        return view('laravel-manager::logs', compact(
            'site',
            'target',
            'web_server_error_file',
            'web_server_access_file',
            'web_server_error_file_content',
            'web_server_access_file_content'
        ));
    }

    public function delete(Request $request)
    {
        $request->validate([
            'site_id' => 'required|integer'
        ]);

        $site = Site::findOrFail((int) $request->input('site_id'));
        $site->delete_wordpress_laravel(); // calls your script and logs

        return back()->with('status', 'Laravel integration removed.');
    }

    public function generateSsl(Request $request)
    {
        // Wire this to your existing internal SSL endpoint/CGI if desired
        // or return a JSON message while you pipe to your CGI script.
        return response()->json(['ok' => true, 'message' => 'SSL generation queued']);
    }

    private function readSshPublicKey(): array
    {
        try {
            $paths = [
                '/root/.ssh/id_rsa.pub',
                base_path('.ssh/id_rsa.pub'),
                storage_path('app/id_rsa.pub'),
            ];
            foreach ($paths as $p) {
                if (is_readable($p)) {
                    return [trim(file_get_contents($p)), null];
                }
            }
            return ['', 'Public key not found in default paths.'];
        } catch (\Throwable $e) {
            return ['', $e->getMessage()];
        }
    }
}
