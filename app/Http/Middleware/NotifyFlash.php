<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every controller still says ->with('success', …) / ->with('error', …); this turns those flashes into
 * Laravel Notify notifications so there is one notification system (rendered with the NEXUS toast style).
 */
class NotifyFlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->isRedirection() && $request->hasSession()) {
            $s = $request->session();
            $list = [];
            foreach (['success' => 'success', 'error' => 'error', 'notice' => 'info', 'warning' => 'warning'] as $key => $method) {
                if ($s->has($key) && is_string($msg = $s->get($key)) && $msg !== '') {
                    notify()->{$method}()->message($msg)->send();
                    $list[] = ['type' => $method, 'message' => $msg];
                }
            }
            if (count($list) > 0) {
                $s->flash('notify_list', $list);   // several messages can ride one redirect; Notify itself keeps only the last
            }
        }

        return $response;
    }
}
