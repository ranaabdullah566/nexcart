<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use App\Services\DemoData;
use App\Support\BusinessProfile;
use App\Support\License;
use App\Support\UpdateChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Health check, version / update notice and demo data. Owner only. */
class SystemController extends Controller
{
    public const REQUIRED_EXTENSIONS = ['mbstring', 'openssl', 'pdo', 'fileinfo', 'tokenizer', 'xml', 'ctype', 'json', 'curl', 'gd', 'zip'];

    public function index(Request $request, BackupService $backups): View
    {
        $this->authorizeTo('system.manage');
        $update = UpdateChecker::latest($request->boolean('check'));

        return view('admin.system.index', [
            'checks' => $this->checks($backups),
            'version' => config('nexcart.version'),
            'update' => $update,
            'updateConfigured' => UpdateChecker::configured(),
            'demo' => app(DemoData::class)->loaded(),
            'license' => License::status(),
            'stats' => ['php' => PHP_VERSION, 'laravel' => app()->version(), 'db' => DB::connection()->getDriverName(), 'env' => app()->environment()],
        ]);
    }

    public function loadDemo(DemoData $demo): RedirectResponse
    {
        $this->authorizeTo('system.manage');

        try {
            $n = $demo->load('pharmacy');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __(':n sample products added.', ['n' => $n]));
    }

    public function removeDemo(DemoData $demo): RedirectResponse
    {
        $this->authorizeTo('system.manage');

        return back()->with('success', __(':n sample products removed.', ['n' => $demo->remove()]));
    }

    /** @return array<int, array{status:string,label:string,detail:string,hint:?string}> status: ok | warn | bad */
    private function checks(BackupService $backups): array
    {
        $c = [];
        $add = function (string $status, string $label, string $detail, ?string $hint = null) use (&$c) {
            $c[] = compact('status', 'label', 'detail', 'hint');
        };

        $add(version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'bad', 'PHP', PHP_VERSION, __('NexCart needs PHP 8.3 or newer.'));

        $missing = array_values(array_filter(self::REQUIRED_EXTENSIONS, fn ($e) => ! extension_loaded($e)));
        $add($missing ? 'bad' : 'ok', __('PHP extensions'), $missing ? implode(', ', $missing) : __('All present'), $missing ? __('Install the missing extensions and restart PHP.') : null);

        try {
            DB::connection()->getPdo();
            $add('ok', __('Database'), DB::connection()->getDriverName().' · '.DB::connection()->getDatabaseName());
        } catch (\Throwable $e) {
            $add('bad', __('Database'), __('Cannot connect'), $e->getMessage());
        }

        $debug = (bool) config('app.debug');
        $add($debug && app()->environment('production') ? 'bad' : ($debug ? 'warn' : 'ok'), __('Debug mode'), $debug ? __('On') : __('Off'), $debug ? __('Set APP_DEBUG=false on a live store — debug pages leak secrets.') : null);

        $https = request()->isSecure();
        $add($https ? 'ok' : (app()->environment('production') ? 'warn' : 'ok'), 'HTTPS', $https ? __('Secure connection') : __('Not using HTTPS'), $https ? null : __('Turn on HTTPS (Let’s Encrypt is free) before taking real orders or card payments.'));

        $linked = is_link(public_path('storage')) || is_dir(public_path('storage'));
        $add($linked ? 'ok' : 'bad', __('Image storage link'), $linked ? __('Present') : __('Missing'), $linked ? null : __('Run: php artisan storage:link'));

        $unwritable = array_values(array_filter([storage_path(), storage_path('app'), storage_path('logs'), base_path('bootstrap/cache')], fn ($p) => ! is_writable($p)));
        $add($unwritable ? 'bad' : 'ok', __('Writable folders'), $unwritable ? implode(', ', array_map('basename', $unwritable)) : __('OK'), $unwritable ? __('The web server user must be able to write to these folders.') : null);

        $mailer = (string) config('mail.default');
        $add(in_array($mailer, ['log', 'array'], true) ? 'warn' : 'ok', __('E-mail'), $mailer, in_array($mailer, ['log', 'array'], true) ? __('E-mails are only written to the log. Set MAIL_MAILER (smtp) so customers receive order e-mails.') : null);

        $beat = (int) Cache::get('nexcart.heartbeat', 0);
        $age = $beat ? now()->timestamp - $beat : null;
        $add($age !== null && $age <= 180 ? 'ok' : 'warn', __('Scheduler'), $age === null ? __('Never ran') : ($age <= 180 ? __('Running') : __('Last ran :m minutes ago', ['m' => intdiv($age, 60)])), $age !== null && $age <= 180 ? null : __('Add the cron entry from the README so reminders and backups run.'));

        $last = $backups->all()[0] ?? null;
        $days = $last ? (int) floor((now()->timestamp - $last['at']) / 86400) : null;
        $add($days === null ? 'warn' : ($days > 7 ? 'warn' : 'ok'), __('Backups'), $last ? __('Last backup :n day(s) ago', ['n' => $days]) : __('No backup yet'), $last && $days <= 7 ? null : __('Create one from the Backups page and turn on daily backups.'));

        if (BusinessProfile::hasModule('prescriptions')) {
            $private = storage_path('app/private');
            $add(is_dir($private) && is_writable($private) || is_writable(storage_path('app')) ? 'ok' : 'warn', __('Prescription storage'), __('Private (not reachable from the web)'));
        }

        return $c;
    }
}
