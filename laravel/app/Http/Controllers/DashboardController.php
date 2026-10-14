<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = request()->user();

        return match ($user->role) {
            'super_admin' => redirect()->route('foundation.dashboard'),
            'school_admin' => redirect()->route('admin.dashboard'),
            'student', 'parent' => redirect()->route('family.dashboard'),
            default => abort(403),
        };
    }
}
