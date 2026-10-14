<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\StudentNote;
use App\Models\Submission;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolAdminController extends Controller
{
    public function index(): View
    {
        $schoolId = request()->user()->school_id;
        abort_unless($schoolId, 403, 'Akun admin belum terhubung ke sekolah.');

        $school = request()->user()->school;

        return view('dashboards.school-admin', [
            'school' => $school,
            'classrooms' => Classroom::where('school_id', $schoolId)->withCount('students')->orderBy('name')->get(),
            'students' => Student::where('school_id', $schoolId)->with(['classroom', 'accounts'])->orderBy('name')->get(),
            'assignments' => Assignment::where('school_id', $schoolId)->with('classroom')->withCount('submissions')->latest()->limit(8)->get(),
            'assignmentCount' => Assignment::where('school_id', $schoolId)->count(),
            'timetables' => Timetable::where('school_id', $schoolId)->with('classroom')->orderBy('weekday')->orderBy('starts_at')->limit(8)->get(),
            'grades' => Grade::where('school_id', $schoolId)->with('student')->latest()->limit(8)->get(),
            'attendances' => Attendance::where('school_id', $schoolId)->with('student')->latest('date')->limit(8)->get(),
            'notes' => StudentNote::where('school_id', $schoolId)->with('student')->latest()->limit(8)->get(),
            'submissions' => Submission::whereHas('assignment', fn ($query) => $query->where('school_id', $schoolId))
                ->with(['assignment', 'student'])->latest('submitted_at')->limit(8)->get(),
            'bills' => StudentBill::where('school_id', $schoolId)->with('student')->latest()->limit(8)->get(),
            'events' => SchoolEvent::where('school_id', $schoolId)->latest('starts_at')->limit(8)->get(),
            'announcements' => SchoolAnnouncement::where('school_id', $schoolId)->latest()->limit(8)->get(),
        ]);
    }

    public function storeClassroom(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('classrooms')->where('school_id', $schoolId)->where('academic_year', $request->input('academic_year'))],
            'academic_year' => ['required', 'string', 'max:20'],
        ]);

        Classroom::create([...$data, 'school_id' => $schoolId]);

        return back()->with('status', 'Kelompok kelas berhasil ditambahkan.');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'nisn' => ['required', 'string', 'max:32', 'unique:students,nisn'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'student_username' => ['required', 'string', 'alpha_dash', 'max:100', 'unique:users,username'],
            'student_password' => ['required', 'string', 'min:8', 'max:255'],
            'parent_username' => ['nullable', 'required_with:parent_password', 'string', 'alpha_dash', 'max:100', 'unique:users,username', 'different:student_username'],
            'parent_password' => ['nullable', 'required_with:parent_username', 'string', 'min:8', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $schoolId): void {
            $student = Student::create([
                'school_id' => $schoolId,
                'classroom_id' => $data['classroom_id'] ?? null,
                'nisn' => $data['nisn'],
                'name' => $data['name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            User::create([
                'name' => $student->name,
                'username' => $data['student_username'],
                'password' => Hash::make($data['student_password']),
                'role' => 'student',
                'school_id' => $schoolId,
                'student_id' => $student->id,
            ]);

            if (! empty($data['parent_username'])) {
                User::create([
                    'name' => 'Orang tua '.$student->name,
                    'username' => $data['parent_username'],
                    'password' => Hash::make($data['parent_password']),
                    'role' => 'parent',
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                ]);
            }
        });

        return back()->with('status', 'Data siswa dan akun pribadinya berhasil dibuat.');
    }

    public function updateStudent(Request $request, int $studentId): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $student = Student::where('school_id', $schoolId)->findOrFail($studentId);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'nisn' => ['required', 'string', 'max:32', Rule::unique('students', 'nisn')->ignore($student->id)],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);
        $student->update($data);
        $student->accounts()->where('role', 'student')->update(['name' => $data['name']]);

        return back()->with('status', 'Data siswa berhasil diperbarui.');
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'subject' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:3000'],
            'due_date' => ['nullable', 'date'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
        ]);

        Assignment::create([...$data, 'school_id' => $schoolId, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Tugas berhasil dibuat.');
    }

    public function storeTimetable(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
            'subject' => ['required', 'string', 'max:100'],
            'teacher' => ['nullable', 'string', 'max:160'],
            'weekday' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:50'],
        ]);

        Timetable::create([...$data, 'school_id' => $schoolId, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function storeGrade(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'subject' => ['required', 'string', 'max:100'],
            'assessment' => ['required', 'string', 'max:160'],
            'score' => ['required', 'numeric', 'between:0,100'],
        ]);

        Grade::create([...$data, 'school_id' => $schoolId, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Nilai berhasil disimpan.');
    }

    public function storeAttendance(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Attendance::updateOrCreate(
            ['student_id' => $data['student_id'], 'date' => $data['date']],
            [...$data, 'school_id' => $schoolId, 'recorded_by' => $request->user()->id],
        );

        return back()->with('status', 'Presensi siswa berhasil disimpan.');
    }

    public function storeNote(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:3000'],
            'visible_to_family' => ['nullable', 'boolean'],
        ]);

        StudentNote::create([
            ...$data,
            'school_id' => $schoolId,
            'created_by' => $request->user()->id,
            'visible_to_family' => $request->boolean('visible_to_family'),
        ]);

        return back()->with('status', 'Catatan siswa berhasil ditambahkan.');
    }

    public function storeBill(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')->where('school_id', $schoolId)],
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'due_date' => ['nullable', 'date'],
        ]);

        StudentBill::create([
            ...$data,
            'school_id' => $schoolId,
            'created_by' => $request->user()->id,
            'status' => 'unpaid',
        ]);

        return back()->with('status', 'Tagihan siswa berhasil dibuat.');
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'starts_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:160'],
        ]);

        SchoolEvent::create([...$data, 'school_id' => $schoolId, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Kegiatan sekolah berhasil ditambahkan.');
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        abort_unless($schoolId, 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:60'],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        SchoolAnnouncement::create([...$data, 'school_id' => $schoolId, 'created_by' => $request->user()->id]);

        return back()->with('status', 'Pengumuman berhasil diterbitkan.');
    }

    public function downloadSubmission(Submission $submission): StreamedResponse
    {
        $schoolId = request()->user()->school_id;
        abort_unless($schoolId, 403);
        abort_unless($submission->assignment()->where('school_id', $schoolId)->exists(), 404);

        return Storage::disk('local')->download($submission->file_path, $submission->original_name);
    }
}
