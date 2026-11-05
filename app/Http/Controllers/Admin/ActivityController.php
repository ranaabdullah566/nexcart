<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $this->authorizeTo('system.manage');

        return view('admin.activity.index', ['entries' => Audit::recent(300)]);
    }
}
