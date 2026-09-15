<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUserIndexRequest;
use App\Http\Requests\UpdateUserAutomationSettingsRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AdminUserAutomationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateUserAutomationSettingsRequest $request): RedirectResponse
    {
        $context = AdminUserIndexRequest::queryContext($request);

        SystemSetting::current()->update([
            'user_anonymization_months' => $request->integer('anonymizationMonths'),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Anonymisierungsfrist gespeichert.'),
        ]);

        return to_route('admin.users.index', $context);
    }
}
