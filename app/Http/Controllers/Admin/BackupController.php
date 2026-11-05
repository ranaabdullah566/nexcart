<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function index(): View
    {
        $this->authorizeTo('system.manage');

        return view('admin.backups.index', ['backups' => $this->backups->all(), 'auto' => (bool) setting('backup.auto', false), 'keep' => (int) setting('backup.keep', 7)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeTo('system.manage');

        try {
            $this->backups->create($request->boolean('uploads', true));
            $this->backups->prune((int) setting('backup.keep', 7));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', __('The backup failed: :why', ['why' => $e->getMessage()]));
        }

        return back()->with('success', __('Backup created.'));
    }

    public function settings(Request $request): RedirectResponse
    {
        $this->authorizeTo('system.manage');
        $data = $request->validate(['keep' => ['required', 'integer', 'min:1', 'max:60']]);
        setting()->setMany(['backup.auto' => $request->boolean('auto'), 'backup.keep' => $data['keep']]);

        return back()->with('success', __('Backup settings saved'));
    }

    public function download(string $name)
    {
        $this->authorizeTo('system.manage');

        try {
            return response()->download($this->backups->path($name));
        } catch (\Throwable) {
            abort(404);
        }
    }

    public function destroy(string $name): RedirectResponse
    {
        $this->authorizeTo('system.manage');

        try {
            unlink($this->backups->path($name));
        } catch (\Throwable) {
            abort(404);
        }

        return back()->with('success', __('Backup deleted'));
    }
}
