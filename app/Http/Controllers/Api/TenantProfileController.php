<?php

namespace App\Http\Controllers\Api;

use App\Actions\UpdateTenantProfile;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantProfileController extends Controller
{
    public function __construct(
        private readonly UpdateTenantProfile $updateTenantProfile,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['tenant' => $this->serialize($request->user()->tenant)]);
    }

    /**
     * Reached over POST rather than PATCH: PHP only parses multipart bodies
     * for POST, and the logos are file uploads.
     */
    public function update(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate(UpdateTenantProfile::rules($tenant->id));

        return response()->json([
            'tenant' => $this->serialize(($this->updateTenantProfile)($tenant, $validated, $request)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'address' => $tenant->address,
            'logo_light_url' => $tenant->logo_light_url,
            'logo_dark_url' => $tenant->logo_dark_url,
        ];
    }
}
