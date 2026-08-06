<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosterTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PosterTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(self::templates($request->string('category')->toString() ?: null));
    }

    public function show(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        abort_unless($posterTemplate->tenant_id === $request->user()->tenant_id, 404);

        return response()->json([
            'id' => $posterTemplate->id,
            'name' => $posterTemplate->name,
            'category' => $posterTemplate->category,
            'type' => $posterTemplate->type,
            'document' => $posterTemplate->document,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function templates(?string $category = null): Collection
    {
        return PosterTemplate::query()
            ->when($category, fn ($q) => $q->where(fn ($w) => $w->where('category', $category)->orWhereNull('category')))
            ->latest('updated_at')
            ->get(['id', 'name', 'category', 'type', 'updated_at'])
            ->map(fn (PosterTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'category' => $t->category,
                'type' => $t->type,
                'updated_at' => $t->updated_at?->toIso8601String(),
            ]);
    }
}
