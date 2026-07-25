<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlyerTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlyerTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = FlyerTemplate::query()
            ->where('is_active', true)
            ->when($request->string('category')->toString(), fn ($query, string $category) => $query->where('category', $category))
            ->latest()
            ->get()
            ->map(fn (FlyerTemplate $flyerTemplate) => $this->serializeTemplate($flyerTemplate));

        return response()->json($templates);
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
}
