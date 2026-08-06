<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackgroundCategory;
use App\Models\PosterBackground;
use App\Models\PosterCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The one screen for everything tenants pick designs from: poster categories, background
 * images, and the categories those images are grouped into. Each used to have its own page;
 * they are managed together often enough that splitting them only added navigation.
 */
class LibraryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $categoryId = $request->integer('category') ?: null;

        return Inertia::render('Admin/Library/Index', [
            'posterCategories' => PosterCategory::query()
                ->withCount('templates')
                ->orderBy('order')
                ->get()
                ->map(fn (PosterCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'order' => $category->order,
                    'templates_count' => $category->templates_count,
                    'is_custom' => $category->slug === PosterCategory::CUSTOM_SLUG,
                ]),
            'backgroundCategories' => BackgroundCategory::query()
                ->withCount('backgrounds')
                ->orderBy('order')
                ->get()
                ->map(fn (BackgroundCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'backgrounds_count' => $category->backgrounds_count,
                ]),
            'backgrounds' => PosterBackground::query()
                ->with('category:id,name')
                ->when($categoryId, fn ($query) => $query->where('background_category_id', $categoryId))
                ->orderBy('order')
                ->latest('id')
                ->get()
                ->map(fn (PosterBackground $background): array => [
                    'id' => $background->id,
                    'name' => $background->name,
                    'url' => $background->url,
                    'is_active' => $background->is_active,
                    'category_id' => $background->background_category_id,
                    'category' => $background->category?->name,
                ]),
            'activeCategory' => $categoryId,
        ]);
    }
}
