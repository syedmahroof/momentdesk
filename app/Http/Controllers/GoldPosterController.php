<?php

namespace App\Http\Controllers;

use App\Enums\Placeholder;
use App\Models\GoldRate;
use App\Models\PosterTemplate;
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
        ]);
    }

    /**
     * @return Collection<int, array{id: int, name: string, category: string|null, type: string|null, updated_at: string|null}>
     */
    private function templates(?string $category = null): Collection
    {
        return PosterTemplate::query()
            ->when($category, fn ($q) => $q->where(fn ($w) => $w->where('category', $category)->orWhereNull('category')))
            ->latest('updated_at')
            ->get(['id', 'name', 'category', 'type', 'updated_at'])
            ->map(fn (PosterTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'category' => $t->category,
                'type' => $t->type,
                'updated_at' => $t->updated_at?->toIso8601String(),
            ]);
    }
}
