<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use App\Support\Trash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Recycle Bin: everything deleted lands here first. Restore it, or erase it for good. */
class TrashController extends Controller
{
    public function index(Request $request): View
    {
        $types = $this->allowed();
        $type = $request->query('type', array_key_first($types));
        abort_unless(isset($types[$type]), 404);

        $counts = collect($types)->map(fn ($t, $k) => Trash::query($k)->count());

        return view('admin.trash.index', [
            'types' => $types, 'type' => $type, 'counts' => $counts,
            'records' => Trash::query($type)->latest('deleted_at')->limit(500)->get(),
            'keepDays' => (int) setting('trash.keep_days', 0),
        ]);
    }

    public function restore(string $type, int $id): RedirectResponse
    {
        $this->authorizeTo('trash.restore');
        $record = $this->find($type, $id);
        $record->restore();
        Audit::log('trash.restored', ['type' => $type, 'id' => $id, 'name' => ($this->allowed()[$type]['name'])($record)]);

        return redirect()->route('admin.trash.index', ['type' => $type])->with('success', __('Restored'));
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $this->authorizeTo('trash.erase');
        $record = $this->find($type, $id);
        $name = ($this->allowed()[$type]['name'])($record);

        if ($record instanceof User && $record->hasRole('owner') && User::role('owner')->count() === 0) {
            return back()->with('error', __('The last owner cannot be deleted permanently.'));
        }
        $record->forceDelete();
        Audit::log('trash.erased', ['type' => $type, 'id' => $id, 'name' => $name]);

        return redirect()->route('admin.trash.index', ['type' => $type])->with('success', __('Deleted permanently'));
    }

    /** Empty the bin of one kind of record. */
    public function empty(string $type): RedirectResponse
    {
        $this->authorizeTo('trash.erase');
        abort_unless(isset($this->allowed()[$type]), 404);

        $n = 0;
        foreach (Trash::query($type)->get() as $record) {
            $record->forceDelete();
            $n++;
        }
        Audit::log('trash.emptied', ['type' => $type, 'count' => $n]);

        return redirect()->route('admin.trash.index', ['type' => $type])->with('success', __(':n items deleted permanently', ['n' => $n]));
    }

    public function settings(Request $request): RedirectResponse
    {
        $this->authorizeTo('system.manage');
        $data = $request->validate(['keep_days' => ['required', 'integer', 'min:0', 'max:3650']]);
        setting()->set('trash.keep_days', $data['keep_days']);

        return back()->with('success', __('Saved'));
    }

    private function allowed(): array
    {
        $types = Trash::allowedFor(auth()->user());
        abort_if($types === [], 403);

        return $types;
    }

    private function find(string $type, int $id)
    {
        abort_unless(isset($this->allowed()[$type]), 404);

        return Trash::query($type)->findOrFail($id);
    }
}
