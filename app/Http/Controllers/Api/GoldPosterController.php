<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoldRate;
use Illuminate\Http\JsonResponse;

class GoldPosterController extends Controller
{
    /**
     * Bundles what the mobile "Update gold rate" screen needs into a single
     * round trip: gold_price poster templates, the latest saved rate, and
     * the rate history — mirrors the props GoldPosterController::daily()
     * hands to the web's GoldPoster/Update.vue page.
     */
    public function daily(): JsonResponse
    {
        $latest = GoldRate::query()->orderByDesc('rate_date')->first();

        return response()->json([
            'templates' => PosterTemplateController::templates('gold_price'),
            'latest_rate' => $latest ? [
                'date' => $latest->rate_date?->format('Y-m-d'),
                'price_22k_1g' => $latest->price_22k_1g,
                'price_22k_8g' => $latest->price_22k_8g,
                'price_18k_1g' => $latest->price_18k_1g,
            ] : null,
            'rates' => GoldRateController::history(),
        ]);
    }
}
