<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDate;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function __construct(private readonly AIService $aiService) {}

    public function generateWish(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'customer_date_id' => ['required', 'exists:customer_dates,id'],
            'tone' => ['nullable', 'in:friendly,formal,professional,warm'],
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $date = CustomerDate::findOrFail($request->customer_date_id);

        $wish = $this->aiService->generateWish($customer, $date, $request->tone ?? 'friendly');

        return response()->json(['message' => $wish]);
    }

    public function improveTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'tone' => ['nullable', 'in:friendly,formal,professional,warm'],
        ]);

        $improved = $this->aiService->improveTemplate($request->content, $request->tone ?? 'friendly');

        return response()->json(['content' => $improved]);
    }
}
