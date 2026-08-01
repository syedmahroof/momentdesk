<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosterCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosterCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        PosterCategory::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'order' => (int) PosterCategory::query()->max('order') + 1,
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, PosterCategory $posterCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $posterCategory->update(['name' => $validated['name']]);

        return back()->with('success', 'Category updated.');
    }

    public function reorder(Request $request, PosterCategory $posterCategory): JsonResponse
    {
        $validated = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ]);

        $neighbor = PosterCategory::query()
            ->when(
                $validated['direction'] === 'up',
                fn ($query) => $query->where('order', '<', $posterCategory->order)->orderByDesc('order'),
                fn ($query) => $query->where('order', '>', $posterCategory->order)->orderBy('order'),
            )
            ->first();

        if ($neighbor) {
            [$a, $b] = [$posterCategory->order, $neighbor->order];
            $posterCategory->update(['order' => $b]);
            $neighbor->update(['order' => $a]);
        }

        return response()->json(['reordered' => true]);
    }

    public function destroy(PosterCategory $posterCategory): RedirectResponse
    {
        abort_if($posterCategory->slug === PosterCategory::CUSTOM_SLUG, 422, 'The Custom category cannot be deleted.');

        $posterCategory->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 1;

        while (PosterCategory::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$suffix;
        }

        return $slug;
    }
}
