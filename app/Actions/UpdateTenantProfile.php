<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Applies a validated tenant profile edit, including the two logo slots.
 * Shared by the web settings page and the mobile API so both handle replaced
 * and removed files the same way.
 */
class UpdateTenantProfile
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function __invoke(Tenant $tenant, array $validated, Request $request): Tenant
    {
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
                $this->deleteExisting($tenant->{$field});
                $attributes[$field] = $request->file("logo_{$variant}")->store('tenant-logos', 'public');
            } elseif ($request->boolean("remove_logo_{$variant}")) {
                $this->deleteExisting($tenant->{$field});
                $attributes[$field] = null;
            }
        }

        $tenant->update($attributes);

        return $tenant->fresh();
    }

    /**
     * The validation rules that go with this action.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(int $tenantId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenantId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo_light' => self::logoRules(),
            'logo_dark' => self::logoRules(),
            'remove_logo_light' => ['nullable', 'boolean'],
            'remove_logo_dark' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function logoRules(): array
    {
        return [
            'nullable',
            'file',
            'extensions:png,jpg,jpeg,webp,svg',
            'mimetypes:image/png,image/jpeg,image/webp,image/svg+xml,text/plain,text/xml,application/xml',
            'max:2048',
        ];
    }

    private function deleteExisting(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
