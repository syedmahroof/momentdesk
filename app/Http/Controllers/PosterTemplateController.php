<?php

namespace App\Http\Controllers;

use App\Models\PosterCategory;
use App\Models\PosterTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosterTemplateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:80'],
            'document' => ['required', 'array'],
        ]);
        $data['category'] = $data['category'] ?? 'gold_price';

        // Tenants can't choose a poster category — anything they save themselves lands under "Custom".
        if ($data['category'] === 'gold_price') {
            $data['poster_category_id'] = $this->customCategoryId();
        }

        $template = PosterTemplate::create($data);

        return response()->json($this->summary($template), 201);
    }

    public function show(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        $this->authorizeRead($request, $posterTemplate);

        return response()->json([
            'id' => $posterTemplate->id,
            'name' => $posterTemplate->name,
            'category' => $posterTemplate->category,
            'type' => $posterTemplate->type,
            'document' => $posterTemplate->document,
            'is_global' => $posterTemplate->tenant_id === null,
        ]);
    }

    public function update(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        $this->authorizeOwn($request, $posterTemplate);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:80'],
            'document' => ['required', 'array'],
        ]);

        if (($data['category'] ?? $posterTemplate->category) === 'gold_price' && ! $posterTemplate->poster_category_id) {
            $data['poster_category_id'] = $this->customCategoryId();
        }

        $posterTemplate->update($data);

        return response()->json($this->summary($posterTemplate));
    }

    public function destroy(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        $this->authorizeOwn($request, $posterTemplate);

        $posterTemplate->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Own templates are always readable; admin-seeded "global" starter designs
     * (tenant_id null) are readable by every tenant but never editable by them.
     */
    private function authorizeRead(Request $request, PosterTemplate $template): void
    {
        abort_unless($template->tenant_id === null || $template->tenant_id === $request->user()->tenant_id, 404);
    }

    private function authorizeOwn(Request $request, PosterTemplate $template): void
    {
        abort_unless($template->tenant_id === $request->user()->tenant_id, 404);
    }

    private function customCategoryId(): ?int
    {
        return PosterCategory::query()->where('slug', PosterCategory::CUSTOM_SLUG)->value('id');
    }

    /**
     * @return array{id: int, name: string, category: string|null, type: string|null, updated_at: string|null}
     */
    private function summary(PosterTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'category' => $template->category,
            'type' => $template->type,
            'updated_at' => $template->updated_at?->toIso8601String(),
        ];
    }
}
