<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** 403 unless the signed-in user holds the permission. */
    protected function authorizeTo(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }
}
