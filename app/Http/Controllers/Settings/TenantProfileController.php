<?php

namespace App\Http\Controllers\Settings;

use App\Actions\UpdateTenantProfile;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantProfileController extends Controller
{
    public function __construct(
        private readonly UpdateTenantProfile $updateTenantProfile,
    ) {}

    public function edit(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('settings/TenantProfile', [
            'tenant' => $tenant,
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate(UpdateTenantProfile::rules($tenant->id));

        ($this->updateTenantProfile)($tenant, $validated, $request);

        return to_route('tenant-profile.edit');
    }
}
