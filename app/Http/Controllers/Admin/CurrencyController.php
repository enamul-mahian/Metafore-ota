<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCurrencyRequest;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    /**
     * Display the currencies.
     */
    public function index(): JsonResponse
    {
        $currencies = Currency::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $currencies,
        ]);
    }

    /**
     * Store a newly created currency.
     */
    public function store(SaveCurrencyRequest $request): JsonResponse
    {
        $currency = Currency::query()->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Currency created successfully.',
            'data' => $currency,
        ], 201);
    }

    /**
     * Display the specified currency.
     */
    public function show(Currency $currency): JsonResponse
    {
        return response()->json([
            'data' => $currency,
        ]);
    }

    /**
     * Update the specified currency.
     */
    public function update(
        SaveCurrencyRequest $request,
        Currency $currency
    ): JsonResponse {
        $currency->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Currency updated successfully.',
            'data' => $currency->fresh(),
        ]);
    }

    /**
     * Remove the specified currency.
     */
    public function destroy(Currency $currency): JsonResponse
    {
        $currency->delete();

        return response()->json([
            'message' => 'Currency deleted successfully.',
        ]);
    }
}
