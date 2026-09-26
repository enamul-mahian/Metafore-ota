<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function create(Request $request, string $service): View
    {
        /** @var array{label: string, title: string, intro: string, icon: string, image: string, fields: array<string, array<string, mixed>>} $definition */
        $definition = config('enquiries.services.'.$service);

        $prefill = collect($request->query())
            ->only([...array_keys($definition['fields']), 'message'])
            ->filter(fn (mixed $value): bool => is_string($value) && mb_strlen($value) <= 500)
            ->all();

        return view('enquiries.create', [
            'service' => $service,
            'definition' => $definition,
            'prefill' => $prefill,
            'sourceUrl' => StoreEnquiryRequest::sameSiteUrl($request->headers->get('referer')),
        ]);
    }

    public function store(StoreEnquiryRequest $request, string $service): RedirectResponse
    {
        $redirect = redirect()->route('enquiries.create', ['service' => $service]);

        // Bots fill the hidden honeypot: answer as usual but store nothing.
        if ($request->filled(StoreEnquiryRequest::HONEYPOT)) {
            return $redirect->with('enquiry_submitted', ['reference' => null]);
        }

        $validated = $request->validated();

        $enquiry = Enquiry::query()->create([
            'service' => $service,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'] ?? null,
            'details' => $request->details(),
            'user_id' => $request->user()?->id,
            'source_url' => StoreEnquiryRequest::sameSiteUrl($validated['source_url'] ?? null),
        ]);

        return $redirect->with('enquiry_submitted', ['reference' => $enquiry->id]);
    }
}
