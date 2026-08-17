<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunUserAutomationCheckRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminUserAutomationCheckController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RunUserAutomationCheckRequest $request): RedirectResponse
    {
        SystemSetting::current()->forceFill([
            'automation_check_requested_at' => now(),
            'automation_check_token' => (string) Str::uuid(),
            'automation_check_completed_at' => null,
            'automation_check_completed_token' => null,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Automatisierungstest gestartet.'),
        ]);

        return to_route('admin.users.index');
    }
}
