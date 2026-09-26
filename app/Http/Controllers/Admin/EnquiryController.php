<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $service = trim((string) $request->query('service', ''));
        $status = trim((string) $request->query('status', ''));

        if (! in_array($service, Enquiry::services(), true)) {
            $service = '';
        }

        if (! in_array($status, Enquiry::STATUSES, true)) {
            $status = '';
        }

        $enquiries = Enquiry::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%');
                });
            })
            ->when($service !== '', fn ($query) => $query->where('service', $service))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.enquiries.index', [
            'enquiries' => $enquiries,
            'filters' => ['search' => $search, 'service' => $service, 'status' => $status],
            'services' => Enquiry::serviceLabels(),
            'statuses' => config('enquiries.statuses'),
            'newCount' => Enquiry::query()->where('status', Enquiry::STATUS_NEW)->count(),
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        return view('admin.enquiries.show', [
            'enquiry' => $enquiry->load('user:id,name,email'),
            'statuses' => config('enquiries.statuses'),
        ]);
    }

    public function update(UpdateEnquiryRequest $request, Enquiry $enquiry): RedirectResponse
    {
        $enquiry->update($request->validated());

        return redirect()
            ->route('admin.enquiries.show', $enquiry)
            ->with('status', 'Enquiry updated successfully.');
    }
}
