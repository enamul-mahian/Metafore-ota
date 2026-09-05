<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RedactedSystemLogReader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class SystemLogPageController extends Controller
{
    public function index(
        Request $request,
        RedactedSystemLogReader $logReader,
    ): View {
        $filters = $request->validate([
            'level' => [
                'nullable',
                'string',
                Rule::in(RedactedSystemLogReader::LEVELS),
            ],
        ]);

        $level = $filters['level'] ?? null;

        return view('admin.system-logs.index', [
            'filters' => ['level' => $level],
            'levels' => RedactedSystemLogReader::LEVELS,
            'logData' => $logReader->read($level),
        ]);
    }
}
