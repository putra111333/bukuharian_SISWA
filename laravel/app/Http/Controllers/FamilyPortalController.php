<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\StudentNote;
use App\Models\Submission;
use App\Models\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class FamilyPortalController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        abort_unless($user->school_id && $user->student_id, 403, 'Akun belum dihubungkan dengan data siswa.');

        $student = Student::where('school_id', $user->school_id)
            ->with('classroom')
            ->findOrFail($user->student_id);
        $classroomId = $student->classroom_id;

        return view('dashboards.family', [
            'student' => $student,
            'grades' => Grade::where('school_id', $user->school_id)->where('student_id', $student->id)->latest()->get(),
            'assignments' => Assignment::where('school_id', $user->school_id)
                ->where(fn ($query) => $query->whereNull('classroom_id')->orWhere('classroom_id', $classroomId))
                ->with(['submissions' => fn ($query) => $query->where('student_id', $student->id)])
                ->with('classroom')
                ->orderBy('due_date')->get(),
            'timetables' => $classroomId
                ? Timetable::where('school_id', $user->school_id)->where('classroom_id', $classroomId)->orderBy('weekday')->orderBy('starts_at')->get()
                : collect(),
            'attendances' => $student->attendances()->latest('date')->limit(10)->get(),
            'notes' => StudentNote::where('school_id', $user->school_id)->where('student_id', $student->id)
                ->where('visible_to_family', true)->latest()->get(),
            'bills' => StudentBill::where('school_id', $user->school_id)->where('student_id', $student->id)->orderBy('due_date')->get(),
            'events' => SchoolEvent::where('school_id', $user->school_id)->where('starts_at', '>=', now())->orderBy('starts_at')->get(),
            'announcements' => SchoolAnnouncement::where('school_id', $user->school_id)->latest()->limit(10)->get(),
            'attendanceSummary' => [
                'hadir' => $student->attendances()->where('status', 'hadir')->count(),
                'izin' => $student->attendances()->where('status', 'izin')->count(),
                'sakit' => $student->attendances()->where('status', 'sakit')->count(),
                'alpa' => $student->attendances()->where('status', 'alpa')->count(),
            ],
        ]);
    }

    public function submit(Request $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->school_id && $user->student_id, 403);
        $student = Student::where('school_id', $user->school_id)->findOrFail($user->student_id);
        abort_unless(
            $assignment->school_id === $student->school_id
            && ($assignment->classroom_id === null || $assignment->classroom_id === $student->classroom_id),
            404,
        );

        $data = $request->validate([
            'submission_file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);
        $file = $data['submission_file'];
        $previousSubmission = Submission::where('assignment_id', $assignment->id)->where('student_id', $student->id)->first();
        $path = $file->store('submissions/'.$student->id, 'local');
        if (! $path) {
            throw new RuntimeException('Berkas tugas tidak berhasil disimpan.');
        }

        Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            ['file_path' => $path, 'original_name' => $file->getClientOriginalName(), 'submitted_at' => now()],
        );

        if ($previousSubmission && $previousSubmission->file_path !== $path) {
            Storage::disk('local')->delete($previousSubmission->file_path);
        }

        return back()->with('status', 'Hasil tugas berhasil diunggah.');
    }
}
