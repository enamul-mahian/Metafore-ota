<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('public.contact');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $redirect = redirect()->to(route('contact').'#message');

        if ($request->filled(StoreEnquiryRequest::HONEYPOT)) {
            return $redirect->with('contact_submitted', ['reference' => null]);
        }

        $validated = $request->validated();

        $enquiry = Enquiry::query()->create([
            'service' => Enquiry::SERVICE_CONTACT,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
            'details' => ['subject' => $validated['subject']],
            'user_id' => $request->user()?->id,
            'source_url' => StoreEnquiryRequest::sameSiteUrl($validated['source_url'] ?? null),
        ]);

        return $redirect->with('contact_submitted', ['reference' => $enquiry->id]);
    }
}
