<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\WorkspaceDashboardData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceDashboardController extends Controller
{
    public function __construct(private readonly WorkspaceDashboardData $dashboardData) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->role === UserRole::User) {
            return redirect()->route('portal');
        }

        return view('workspace.dashboard', $this->dashboardData->for($request->user()));
    }
}
