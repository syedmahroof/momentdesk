<?php

namespace App\Http\Controllers;

use App\Models\GoldRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GoldRateController extends Controller
{
    public function latest(): JsonResponse
    {
        $rate = GoldRate::query()->orderByDesc('rate_date')->first();

        return response()->json(['rate' => $rate ? $this->rate($rate) : null]);
    }

    public function index(): JsonResponse
    {
        return response()->json(['rates' => self::history()]);
    }

    /**
     * The rate history screen — the table and trend chart live here rather than on the
     * daily update page, which stays focused on producing the poster.
     */
    public function historyPage(): Response
    {
        return Inertia::render('GoldPoster/RateHistory', [
            'rates' => self::history(),
        ]);
    }

    /**
     * The trailing rate history (oldest first), for the table and chart.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function history(int $limit = 60): Collection
    {
        return GoldRate::query()
            ->orderByDesc('rate_date')
            ->limit($limit)
            ->get()
            ->sortBy('rate_date')
            ->values()
            ->map(fn (GoldRate $r) => [
                'date' => $r->rate_date?->format('Y-m-d'),
                'price_22k_1g' => $r->price_22k_1g,
                'price_22k_8g' => $r->price_22k_8g,
                'price_18k_1g' => $r->price_18k_1g,
            ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'price_22k_1g' => ['required', 'integer', 'min:0'],
            'price_22k_8g' => ['required', 'integer', 'min:0'],
            'price_18k_1g' => ['required', 'integer', 'min:0'],
        ]);

        // The most recent rate on an earlier date — used for the trend comparison.
        $previous = GoldRate::query()
            ->whereDate('rate_date', '<', $data['date'])
            ->orderByDesc('rate_date')
            ->first();

        $rate = GoldRate::updateOrCreate(
            ['rate_date' => $data['date']],
            [
                'price_22k_1g' => $data['price_22k_1g'],
                'price_22k_8g' => $data['price_22k_8g'],
                'price_18k_1g' => $data['price_18k_1g'],
            ],
        );

        // Only the 1 gram 22K price drives the trend.
        $diff = $previous ? $rate->price_22k_1g - $previous->price_22k_1g : 0;

        return response()->json([
            'rate' => $this->rate($rate),
            'trend' => [
                'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'same'),
                'diff' => abs($diff),
                'has_previous' => (bool) $previous,
            ],
        ]);
    }

    /**
     * @return array{date: string|null, price_22k_1g: int, price_22k_8g: int, price_18k_1g: int}
     */
    private function rate(GoldRate $rate): array
    {
        return [
            'date' => $rate->rate_date?->format('Y-m-d'),
            'price_22k_1g' => $rate->price_22k_1g,
            'price_22k_8g' => $rate->price_22k_8g,
            'price_18k_1g' => $rate->price_18k_1g,
        ];
    }
}
