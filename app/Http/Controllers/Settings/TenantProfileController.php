<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantProfileController extends Controller
{
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

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenant->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo_light' => ['nullable', 'file', 'extensions:png,jpg,jpeg,webp,svg', 'mimetypes:image/png,image/jpeg,image/webp,image/svg+xml,text/plain,text/xml,application/xml', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'extensions:png,jpg,jpeg,webp,svg', 'mimetypes:image/png,image/jpeg,image/webp,image/svg+xml,text/plain,text/xml,application/xml', 'max:2048'],
            'remove_logo_light' => ['nullable', 'boolean'],
            'remove_logo_dark' => ['nullable', 'boolean'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'slug' => Str::slug($validated['name']),
        ];

        foreach (['light', 'dark'] as $variant) {
            $field = "logo_{$variant}_path";

            if ($request->hasFile("logo_{$variant}")) {
                if ($tenant->{$field}) {
                    Storage::disk('public')->delete($tenant->{$field});
                }
                $attributes[$field] = $request->file("logo_{$variant}")->store('tenant-logos', 'public');
            } elseif ($request->boolean("remove_logo_{$variant}")) {
                if ($tenant->{$field}) {
                    Storage::disk('public')->delete($tenant->{$field});
                }
                $attributes[$field] = null;
            }
        }

        $tenant->update($attributes);

        return to_route('tenant-profile.edit');
    }
}
