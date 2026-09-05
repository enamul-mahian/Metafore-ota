<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveStudentRequest;
use App\Models\Country;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        if (! in_array($status, Student::STATUSES, true)) {
            $status = '';
        }

        $students = Student::query()->with('country:id,name,iso2')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('reference_code', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'filters' => ['search' => $search, 'status' => $status],
            'statuses' => Student::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', ['countries' => $this->countries(), 'statuses' => Student::STATUSES]);
    }

    public function store(SaveStudentRequest $request): RedirectResponse
    {
        $student = Student::query()->create($request->validated());

        return redirect()->route('admin.students.show', $student)->with('status', 'Student created successfully.');
    }

    public function show(Student $student): View
    {
        return view('admin.students.show', ['student' => $student->load('country:id,name,iso2')]);
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', [
            'student' => $student, 'countries' => $this->countries(), 'statuses' => Student::STATUSES,
        ]);
    }

    public function update(SaveStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return redirect()->route('admin.students.show', $student)->with('status', 'Student updated successfully.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('admin.students.index')->with('status', 'Student deleted successfully.');
    }

    /** @return Collection<int, Country> */
    private function countries(): Collection
    {
        return Country::query()->orderBy('name')->get(['id', 'name', 'iso2']);
    }
}
