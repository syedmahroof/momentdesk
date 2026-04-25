<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlyerTemplateRequest;
use App\Models\FlyerTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FlyerTemplateController extends Controller
{
    public function index(): Response
    {
        $flyerTemplates = FlyerTemplate::query()
            ->latest()
            ->get()
            ->map(fn (FlyerTemplate $flyerTemplate) => $this->serializeTemplate($flyerTemplate));

        return Inertia::render('FlyerTemplates/Index', [
            'flyerTemplates' => $flyerTemplates,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('FlyerTemplates/Create');
    }

    public function store(FlyerTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('background_image')) {
            $validated['background_image_path'] = $request->file('background_image')->store('flyer-templates/backgrounds', 'public');
        }

        if (($validated['background_type'] ?? 'color') === 'color' || ($validated['remove_background_image'] ?? false)) {
            $validated['background_image_path'] = null;
        }

        unset($validated['background_image'], $validated['remove_background_image']);

        FlyerTemplate::create($validated);

        return redirect()->route('flyer-templates.index')->with('success', 'Flyer template created.');
    }

    public function edit(FlyerTemplate $flyerTemplate): Response
    {
        return Inertia::render('FlyerTemplates/Edit', [
            'flyerTemplate' => $this->serializeTemplate($flyerTemplate),
        ]);
    }

    public function update(FlyerTemplateRequest $request, FlyerTemplate $flyerTemplate): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('background_image')) {
            $validated['background_image_path'] = $request->file('background_image')->store('flyer-templates/backgrounds', 'public');
        } else {
            $validated['background_image_path'] = $flyerTemplate->background_image_path;
        }

        if (($validated['background_type'] ?? 'color') === 'color' || ($validated['remove_background_image'] ?? false)) {
            $validated['background_image_path'] = null;
        }

        unset($validated['background_image'], $validated['remove_background_image']);

        $flyerTemplate->update($validated);

        return redirect()->route('flyer-templates.index')->with('success', 'Flyer template updated.');
    }

    public function destroy(FlyerTemplate $flyerTemplate): RedirectResponse
    {
        $flyerTemplate->delete();

        return redirect()->route('flyer-templates.index')->with('success', 'Flyer template deleted.');
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
            'background_image_path' => $flyerTemplate->background_image_path,
            'background_image_url' => $flyerTemplate->background_image_path
                ? Storage::disk('public')->url($flyerTemplate->background_image_path)
                : null,
            'elements' => $flyerTemplate->elements,
            'is_active' => $flyerTemplate->is_active,
            'created_at' => $flyerTemplate->created_at?->diffForHumans(),
        ];
    }
}
