<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeTo('activity.view');

        return view('admin.activity.index', [
            'entries' => $this->query($request)->with(['causer', 'subject' => fn ($q) => method_exists($q->getModel(), 'bootSoftDeletes') ? $q->withTrashed() : $q])->paginate(40)->withQueryString(),
            'events' => Activity::query()->distinct()->orderBy('event')->pluck('event')->filter()->values(),
            'subjects' => Activity::query()->whereNotNull('subject_type')->distinct()->pluck('subject_type')->map(fn ($t) => ['class' => $t, 'label' => class_basename($t)])->sortBy('label')->values(),
            'users' => User::whereHas('roles')->orderBy('name')->get(['id', 'name']),
            'f' => $request->only(['q', 'event', 'user', 'subject', 'from', 'to']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeTo('activity.view');
        $this->authorizeTo('reports.export');

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['when', 'user', 'event', 'record', 'record_id', 'changes', 'ip']);
            $this->query($request)->with('causer')->chunkById(500, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v, [
                        local_time($e->created_at, 'Y-m-d H:i:s'), $e->causer?->name, $e->event, $e->subject_type ? class_basename($e->subject_type) : '', $e->subject_id,
                        json_encode($e->properties->except(['ip', 'ua']), JSON_UNESCAPED_UNICODE), $e->properties['ip'] ?? '',
                    ]));
                }
            });
            fclose($out);
        }, 'activity-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(Request $request)
    {
        $f = $request->only(['q', 'event', 'user', 'subject', 'from', 'to']);
        $tz = setting('store.timezone', config('app.timezone'));

        return Activity::query()->latest('id')
            ->when($f['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($f['user'] ?? null, fn ($q, $v) => $q->where('causer_id', (int) $v)->where('causer_type', User::class))
            ->when($f['subject'] ?? null, fn ($q, $v) => $q->where('subject_type', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($v, $tz)->startOfDay()->utc()))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($v, $tz)->endOfDay()->utc()))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('description', 'like', '%'.$v.'%')->orWhere('properties', 'like', '%'.$v.'%')));
    }
}
