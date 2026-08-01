<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Placeholder;
use App\Http\Controllers\Controller;
use App\Models\PosterCategory;
use App\Models\PosterTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PosterTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('GoldPoster/Index', [
            'tenant' => null,
            'templates' => $this->templates(),
            'open' => $request->integer('template') ?: null,
            'placeholders' => Placeholder::options(),
            'posterCategories' => PosterCategory::query()->orderBy('order')->get(['id', 'name']),
            'adminMode' => true,
        ]);
    }

    public function show(PosterTemplate $posterTemplate): JsonResponse
    {
        abort_unless($posterTemplate->tenant_id === null, 404);

        return response()->json([
            'id' => $posterTemplate->id,
            'name' => $posterTemplate->name,
            'category' => $posterTemplate->category,
            'type' => $posterTemplate->type,
            'poster_category_id' => $posterTemplate->poster_category_id,
            'document' => $posterTemplate->document,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $template = PosterTemplate::create([...$data, 'tenant_id' => null]);

        return response()->json($this->summary($template), 201);
    }

    public function update(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        abort_unless($posterTemplate->tenant_id === null, 404);

        $posterTemplate->update($this->validated($request));

        return response()->json($this->summary($posterTemplate));
    }

    public function destroy(PosterTemplate $posterTemplate): JsonResponse
    {
        abort_unless($posterTemplate->tenant_id === null, 404);

        $posterTemplate->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * @return array{name: string, category: string, type: string|null, poster_category_id: int|null, document: array<string, mixed>}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:80'],
            'poster_category_id' => ['nullable', 'integer', 'exists:poster_categories,id'],
            'document' => ['required', 'array'],
        ]);

        $data['category'] = $data['category'] ?? 'gold_price';

        return $data;
    }

    /**
     * @return Collection<int, array{id: int, name: string, category: string|null, type: string|null, updated_at: string|null, poster_category: string|null, is_global: bool}>
     */
    private function templates(): Collection
    {
        return PosterTemplate::query()
            ->whereNull('tenant_id')
            ->with('posterCategory:id,name,order')
            ->latest('updated_at')
            ->get(['id', 'tenant_id', 'name', 'category', 'type', 'poster_category_id', 'updated_at'])
            ->map(fn (PosterTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'category' => $t->category,
                'type' => $t->type,
                'updated_at' => $t->updated_at?->toIso8601String(),
                'poster_category' => $t->posterCategory?->name,
                'poster_category_id' => $t->poster_category_id,
                'is_global' => true,
            ]);
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
