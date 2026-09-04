<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\View\View;

class LanguagePageController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.languages.index', [
            'languages' => Language::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
