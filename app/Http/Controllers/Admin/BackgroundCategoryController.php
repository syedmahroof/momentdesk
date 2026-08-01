<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackgroundCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BackgroundCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        BackgroundCategory::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'order' => (int) BackgroundCategory::query()->max('order') + 1,
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, BackgroundCategory $backgroundCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $backgroundCategory->update(['name' => $validated['name']]);

        return back()->with('success', 'Category updated.');
    }

    /**
     * Images in a deleted category are kept — they simply become uncategorized.
     */
    public function destroy(BackgroundCategory $backgroundCategory): RedirectResponse
    {
        $backgroundCategory->delete();

        return back()->with('success', 'Category deleted.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 1;

        while (BackgroundCategory::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$suffix;
        }

        return $slug;
    }
}
