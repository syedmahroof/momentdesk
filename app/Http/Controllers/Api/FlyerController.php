<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlyerRequest;
use App\Models\Flyer;
use App\Models\FlyerTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlyerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $flyers = Flyer::query()
            ->with('flyerTemplate')
            ->when($request->string('category')->toString(), function ($query, string $category) {
                $query->whereHas('flyerTemplate', fn ($query) => $query->where('category', $category));
            })
            ->latest()
            ->paginate(20)
            ->through(fn (Flyer $flyer) => $this->serializeFlyer($flyer));

        return response()->json($flyers);
    }

    public function store(FlyerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $flyerTemplate = FlyerTemplate::query()->findOrFail($validated['flyer_template_id']);

        $assetPaths = array_filter([
            'logo' => $request->hasFile('logo_image')
                ? $request->file('logo_image')->store('flyers/assets', 'public')
                : null,
            'featured_image' => $request->hasFile('featured_image')
                ? $request->file('featured_image')->store('flyers/assets', 'public')
                : null,
            'background' => $request->hasFile('background_image')
                ? $request->file('background_image')->store('flyers/assets', 'public')
                : null,
        ]);

        $templateSnapshot = [
            'title' => $flyerTemplate->title,
            'category' => $flyerTemplate->category,
            'paper_size' => $flyerTemplate->paper_size,
            'canvas_width' => $flyerTemplate->canvas_width,
            'canvas_height' => $flyerTemplate->canvas_height,
            'background_type' => $flyerTemplate->background_type,
            'background_color' => $flyerTemplate->background_color,
            'background_image_path' => $flyerTemplate->background_image_path,
            'background_image_url' => $flyerTemplate->background_image_path
                ? Storage::disk('public')->url($flyerTemplate->background_image_path)
                : null,
            'elements' => $flyerTemplate->elements,
        ];

        if (! empty($assetPaths['background'])) {
            $path = $assetPaths['background'];
            $templateSnapshot['background_type'] = 'image';
            $templateSnapshot['background_image_path'] = $path;
            $templateSnapshot['background_image_url'] = Storage::disk('public')->url($path);
        }

        $flyer = Flyer::create([
            'flyer_template_id' => $flyerTemplate->id,
            'title' => $validated['title'],
            'paper_size' => $validated['paper_size'],
            'canvas_width' => $validated['canvas_width'],
            'canvas_height' => $validated['canvas_height'],
            'field_values' => $validated['field_values'] ?? [],
            'element_overrides' => $validated['element_overrides'] ?? [],
            'asset_paths' => $assetPaths,
            'template_snapshot' => $templateSnapshot,
        ]);

        return response()->json($this->serializeFlyer($flyer->load('flyerTemplate')), 201);
    }

    public function show(Flyer $flyer): JsonResponse
    {
        return response()->json($this->serializeFlyer($flyer->load('flyerTemplate')));
    }

    public function destroy(Flyer $flyer): JsonResponse
    {
        $flyer->delete();

        return response()->json(['message' => 'Flyer deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFlyer(Flyer $flyer): array
    {
        return [
            'id' => $flyer->id,
            'title' => $flyer->title,
            'paper_size' => $flyer->paper_size,
            'canvas_width' => $flyer->canvas_width,
            'canvas_height' => $flyer->canvas_height,
            'field_values' => $flyer->field_values ?? [],
            'element_overrides' => $flyer->element_overrides ?? [],
            'asset_paths' => $flyer->asset_paths ?? [],
            'asset_urls' => collect($flyer->asset_paths ?? [])
                ->mapWithKeys(fn (string $path, string $key) => [$key => Storage::disk('public')->url($path)])
                ->all(),
            'template_snapshot' => $flyer->template_snapshot,
            'flyer_template' => $flyer->flyerTemplate
                ? [
                    'id' => $flyer->flyerTemplate->id,
                    'title' => $flyer->flyerTemplate->title,
                ]
                : null,
            'created_at' => $flyer->created_at?->toIso8601String(),
        ];
    }
}
