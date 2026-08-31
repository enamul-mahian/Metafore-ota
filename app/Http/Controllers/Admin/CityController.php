<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCityRequest;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    /**
     * Display all cities.
     */
    public function index(): JsonResponse
    {
        $cities = City::query()
            ->with('country:id,name,iso2,iso3')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $cities,
        ]);
    }

    /**
     * Store a newly created city.
     */
    public function store(SaveCityRequest $request): JsonResponse
    {
        $city = City::query()->create($request->validated());

        $city->load('country:id,name,iso2,iso3');

        return response()->json([
            'message' => 'City created successfully.',
            'data' => $city,
        ], 201);
    }

    /**
     * Display the specified city.
     */
    public function show(City $city): JsonResponse
    {
        $city->load('country:id,name,iso2,iso3');

        return response()->json([
            'data' => $city,
        ]);
    }

    /**
     * Update the specified city.
     */
    public function update(
        SaveCityRequest $request,
        City $city
    ): JsonResponse {
        $city->update($request->validated());

        $city->refresh();
        $city->load('country:id,name,iso2,iso3');

        return response()->json([
            'message' => 'City updated successfully.',
            'data' => $city,
        ]);
    }

    /**
     * Remove the specified city.
     */
    public function destroy(City $city): JsonResponse
    {
        $city->delete();

        return response()->json([
            'message' => 'City deleted successfully.',
        ]);
    }
}