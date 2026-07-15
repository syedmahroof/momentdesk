<?php

namespace App\Http\Controllers;

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

        $template = PosterTemplate::create($data);

        return response()->json($this->summary($template), 201);
    }

    public function show(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        $this->authorizeTenant($request, $posterTemplate);

        return response()->json([
            'id' => $posterTemplate->id,
            'name' => $posterTemplate->name,
            'category' => $posterTemplate->category,
            'type' => $posterTemplate->type,
            'document' => $posterTemplate->document,
        ]);
    }

    public function update(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        $this->authorizeTenant($request, $posterTemplate);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:80'],
            'document' => ['required', 'array'],
        ]);

        $posterTemplate->update($data);

        return response()->json($this->summary($posterTemplate));
    }

    public function destroy(Request $request, PosterTemplate $posterTemplate): JsonResponse
    {
        $this->authorizeTenant($request, $posterTemplate);

        $posterTemplate->delete();

        return response()->json(['deleted' => true]);
    }

    private function authorizeTenant(Request $request, PosterTemplate $template): void
    {
        abort_unless($template->tenant_id === $request->user()->tenant_id, 404);
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
