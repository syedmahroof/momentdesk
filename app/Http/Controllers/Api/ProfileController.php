<?php

namespace App\Http\Controllers\Api;

use App\Concerns\IssuesApiTokens;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    use IssuesApiTokens;

    /**
     * Shares the web app's request object, so the same uniqueness and length
     * rules apply from either client.
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        // Changing the address invalidates whatever confirmation the old one
        // had, exactly as the web profile form does.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json(['user' => $this->serializeUser($user)]);
    }

    /**
     * Deletes the account and every token issued to it, so other devices are
     * signed out too.
     */
    public function destroy(ProfileDeleteRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Your account has been deleted.']);
    }
}
