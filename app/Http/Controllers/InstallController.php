<?php

namespace App\Http\Controllers;

use App\Support\Installer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Step 1 of the web installer (step 2 is the store wizard at /setup). */
class InstallController extends Controller
{
    public function __construct(private Installer $installer) {}

    public function show(): View|RedirectResponse
    {
        if (! $this->installer->available()) {
            return redirect()->route(Installer::locked() ? 'login' : 'setup');
        }

        return view('install.index', [
            'checks' => $this->installer->requirements(),
            'ready' => $this->installer->requirementsMet(),
            'sqlite' => database_path('database.sqlite'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->installer->available(), 404);
        if (! $this->installer->requirementsMet()) {
            throw ValidationException::withMessages(['driver' => __('Fix the red items above first.')]);
        }

        $data = $request->validate([
            'driver' => ['required', 'in:mysql,sqlite'],
            'host' => ['required_if:driver,mysql', 'nullable', 'string', 'max:190', 'regex:/^[A-Za-z0-9._\-]+$/'],
            'port' => ['required_if:driver,mysql', 'nullable', 'integer', 'between:1,65535'],
            'database' => ['required_if:driver,mysql', 'nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-$]+$/'],
            'username' => ['required_if:driver,mysql', 'nullable', 'string', 'max:64'],
            'password' => ['nullable', 'string', 'max:200'],
        ]);

        $db = $data['driver'] === 'sqlite'
            ? ['driver' => 'sqlite', 'database' => database_path('database.sqlite')]
            : ['driver' => 'mysql', 'host' => $data['host'], 'port' => (int) $data['port'], 'database' => $data['database'], 'username' => $data['username'], 'password' => (string) ($data['password'] ?? '')];

        $test = $this->installer->testConnection($db);
        if (! $test['ok']) {
            throw ValidationException::withMessages(['host' => __('Could not connect: :why', ['why' => $test['error']])]);
        }

        try {
            $this->installer->apply($db, $request->getSchemeAndHttpHost());
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['host' => $e->getMessage()]);
        }

        return redirect()->route('setup')->with('notice', __('Database ready. Now set up your store.'));
    }
}
