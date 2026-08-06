<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosterBackground;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PosterBackgroundController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'background_category_id' => ['nullable', 'integer', 'exists:background_categories,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        foreach ($validated['images'] as $index => $image) {
            /** @var UploadedFile $image */
            $path = $image->store('poster-backgrounds', 'public');

            PosterBackground::create([
                'background_category_id' => $validated['background_category_id'] ?? null,
                'name' => $this->nameFor($validated['name'] ?? null, $image, $index, count($validated['images'])),
                'path' => $path,
                'is_active' => true,
                'order' => (int) PosterBackground::query()->max('order') + 1,
            ]);
        }

        return back()->with('success', 'Background images uploaded.');
    }

    public function update(Request $request, PosterBackground $posterBackground): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'background_category_id' => ['nullable', 'integer', 'exists:background_categories,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        $posterBackground->update($validated);

        return back()->with('success', 'Background updated.');
    }

    public function destroy(PosterBackground $posterBackground): RedirectResponse
    {
        Storage::disk('public')->delete($posterBackground->path);
        $posterBackground->delete();

        return back()->with('success', 'Background deleted.');
    }

    /**
     * Uploads share one name when a single file is sent; batches fall back to the file name.
     */
    private function nameFor(?string $name, UploadedFile $image, int $index, int $total): string
    {
        if ($name && $total === 1) {
            return $name;
        }

        if ($name) {
            return $name.' '.($index + 1);
        }

        return pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Background';
    }
}
