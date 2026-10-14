<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FoundationController extends Controller
{
    public function index(): View
    {
        return view('dashboards.foundation', [
            'schools' => School::withCount('students')->orderBy('name')->get(),
            'administrators' => User::where('role', 'school_admin')->with('school')->orderBy('name')->get(),
            'schoolCount' => School::count(),
            'studentCount' => Student::count(),
            'staffCount' => User::where('role', 'school_admin')->count(),
        ]);
    }

    public function storeSchool(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        School::create($attributes);

        return back()->with('status', 'Sekolah berhasil ditambahkan.');
    }

    public function updateSchool(Request $request, School $school): RedirectResponse
    {
        $school->update($request->validate([
            'name' => ['required', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]));

        return back()->with('status', 'Informasi sekolah berhasil diperbarui.');
    }

    public function storeAdministrator(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'username' => ['required', 'string', 'alpha_dash', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'job_title' => ['required', Rule::in(['Kepala sekolah', 'Guru', 'Tata usaha'])],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
        ]);

        User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'role' => 'school_admin',
        ]);

        return back()->with('status', 'Akun administrator sekolah berhasil dibuat.');
    }
}
