<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\View\View;

class CurrencyPageController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.currencies.index', [
            'currencies' => Currency::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
