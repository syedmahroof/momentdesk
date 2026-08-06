<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoldRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

        $previous = GoldRate::query()
            ->whereDate('rate_date', '<', $data['date'])
            ->orderByDesc('rate_date')
            ->first();

        $rate = GoldRate::updateOrCreate(
            ['rate_date' => $data['date']],
            ['price_22k_1g' => $data['price_22k_1g'], 'price_22k_8g' => $data['price_22k_8g'], 'price_18k_1g' => $data['price_18k_1g']],
        );

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
     * @return array<string, mixed>
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
