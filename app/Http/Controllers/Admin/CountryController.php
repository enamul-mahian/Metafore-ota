<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCountryRequest;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    /**
     * Display the countries.
     */
    public function index(): JsonResponse
    {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $countries,
        ]);
    }

    /**
     * Store a newly created country.
     */
    public function store(SaveCountryRequest $request): JsonResponse
    {
        $country = Country::query()->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Country created successfully.',
            'data' => $country,
        ], 201);
    }

    /**
     * Display the specified country.
     */
    public function show(Country $country): JsonResponse
    {
        return response()->json([
            'data' => $country,
        ]);
    }

    /**
     * Update the specified country.
     */
    public function update(
        SaveCountryRequest $request,
        Country $country
    ): JsonResponse {
        $country->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Country updated successfully.',
            'data' => $country->fresh(),
        ]);
    }

    /**
     * Remove the specified country.
     */
    public function destroy(Country $country): JsonResponse
    {
        if (
            $country->cities()
                ->exists()
        ) {
            return response()->json([
                'message' => 'Country cannot be deleted while cities are associated with it.',
            ], 409);
        }

        $country->delete();

        return response()->json([
            'message' => 'Country deleted successfully.',
        ]);
    }
}