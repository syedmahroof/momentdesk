<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlyerRequest;
use App\Models\Flyer;
use App\Models\FlyerTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FlyerController extends Controller
{
    public function index(): Response
    {
        $flyers = Flyer::query()
            ->with('flyerTemplate')
            ->latest()
            ->get()
            ->map(fn (Flyer $flyer) => $this->serializeFlyer($flyer));

        return Inertia::render('Flyers/Index', [
            'flyers' => $flyers,
        ]);
    }

    public function previewPrint(): Response
    {
        $flyers = Flyer::query()
            ->with('flyerTemplate')
            ->latest()
            ->limit(60)
            ->get()
            ->map(fn (Flyer $flyer) => $this->serializeFlyer($flyer));

        return Inertia::render('Flyers/PreviewPrint', [
            'flyers' => $flyers,
        ]);
    }

    public function create(): Response
    {
        $templates = FlyerTemplate::query()
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(fn (FlyerTemplate $flyerTemplate) => $this->serializeTemplate($flyerTemplate));

        return Inertia::render('Flyers/Create', [
            'templates' => $templates,
        ]);
    }

    public function store(FlyerRequest $request): RedirectResponse
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

        return redirect()->route('flyers.show', $flyer)->with('success', 'Flyer generated.');
    }

    public function show(Flyer $flyer): Response
    {
        return Inertia::render('Flyers/Show', [
            'flyer' => $this->serializeFlyer($flyer->load('flyerTemplate')),
        ]);
    }

    public function destroy(Flyer $flyer): RedirectResponse
    {
        $flyer->delete();

        return redirect()->route('flyers.index')->with('success', 'Flyer deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTemplate(FlyerTemplate $flyerTemplate): array
    {
        return [
            'id' => $flyerTemplate->id,
            'title' => $flyerTemplate->title,
            'category' => $flyerTemplate->category,
            'paper_size' => $flyerTemplate->paper_size,
            'canvas_width' => $flyerTemplate->canvas_width,
            'canvas_height' => $flyerTemplate->canvas_height,
            'background_type' => $flyerTemplate->background_type,
            'background_color' => $flyerTemplate->background_color,
            'background_image_url' => $flyerTemplate->background_image_path
                ? Storage::disk('public')->url($flyerTemplate->background_image_path)
                : null,
            'elements' => $flyerTemplate->elements,
        ];
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
            'created_at' => $flyer->created_at?->diffForHumans(),
        ];
    }
}
