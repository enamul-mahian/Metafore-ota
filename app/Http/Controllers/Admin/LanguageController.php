<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveLanguageRequest;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Language::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(SaveLanguageRequest $request): JsonResponse
    {
        $language = Language::query()->create($request->validated());

        return response()->json([
            'message' => 'Language created successfully.',
            'data' => $language,
        ], 201);
    }

    public function show(Language $language): JsonResponse
    {
        return response()->json(['data' => $language]);
    }

    public function update(
        SaveLanguageRequest $request,
        Language $language
    ): JsonResponse {
        $language->update($request->validated());

        return response()->json([
            'message' => 'Language updated successfully.',
            'data' => $language->fresh(),
        ]);
    }

    public function destroy(Language $language): JsonResponse
    {
        $language->delete();

        return response()->json([
            'message' => 'Language deleted successfully.',
        ]);
    }
}
