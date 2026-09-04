<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class CategoryPageController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
