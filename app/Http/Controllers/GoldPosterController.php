<?php

namespace App\Http\Controllers;

use App\Enums\Placeholder;
use App\Models\GoldRate;
use App\Models\PosterBackground;
use App\Models\PosterTemplate;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GoldPosterController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('GoldPoster/Index', [
            'tenant' => $request->user()->tenant,
            'templates' => $this->templates(),
            'open' => $request->integer('template') ?: null,
            'placeholders' => Placeholder::options(),
            'backgrounds' => self::backgrounds(),
        ]);
    }

    /**
     * Active background images from the admin-managed library, ready for the editor.
     *
     * @return Collection<int, array{id: int, name: string, url: string, category: string|null}>
     */
    public static function backgrounds(): Collection
    {
        return PosterBackground::query()
            ->where('is_active', true)
            ->with('category:id,name,order')
            ->orderBy('order')
            ->get()
            ->map(fn (PosterBackground $background) => [
                'id' => $background->id,
                'name' => $background->name,
                'url' => $background->url,
                'category' => $background->category?->name,
            ]);
    }

    public function templatesPage(): Response
    {
        return Inertia::render('GoldPoster/Templates', [
            'templates' => $this->templates(),
        ]);
    }

    public function daily(Request $request): Response
    {
        $latest = GoldRate::query()->orderByDesc('rate_date')->first();

        return Inertia::render('GoldPoster/Update', [
            'tenant' => $request->user()->tenant,
            'templates' => $this->templates('gold_price'),
            'latestRate' => $latest ? [
                'date' => $latest->rate_date?->format('Y-m-d'),
                'price_22k_1g' => $latest->price_22k_1g,
                'price_22k_8g' => $latest->price_22k_8g,
                'price_18k_1g' => $latest->price_18k_1g,
            ] : null,
            'rates' => GoldRateController::history(),
            'backgrounds' => self::backgrounds(),
        ]);
    }

    /**
     * @return Collection<int, array{id: int, name: string, category: string|null, type: string|null, updated_at: string|null, poster_category: string|null, is_global: bool}>
     */
    private function templates(?string $category = null): Collection
    {
        $tenantId = auth()->user()?->tenant_id;

        return PosterTemplate::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where(fn ($w) => $w->where('tenant_id', $tenantId)->orWhereNull('tenant_id'))
            ->when($category, fn ($q) => $q->where(fn ($w) => $w->where('category', $category)->orWhereNull('category')))
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
                'is_global' => $t->tenant_id === null,
            ]);
    }
}
