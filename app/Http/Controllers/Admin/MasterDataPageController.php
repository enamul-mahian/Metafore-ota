<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use Illuminate\View\View;

class MasterDataPageController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.master-data.index', [
            'countries' => Country::query()->orderBy('name')->get(),
            'cities' => City::query()->with('country:id,name,iso2')->orderBy('name')->get(),
        ]);
    }
}
