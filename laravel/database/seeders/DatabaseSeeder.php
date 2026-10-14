<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolAnnouncement;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\StudentBill;
use App\Models\StudentNote;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('SIMANIS demo accounts must not be seeded in production.');
        }

        $school = School::firstOrCreate(
            ['name' => 'SMA Cendekia'],
            ['address' => 'Jakarta', 'status' => 'active'],
        );
        $classroom = Classroom::firstOrCreate(
            ['school_id' => $school->id, 'name' => '8B', 'academic_year' => '2026/27'],
        );
        $student = Student::firstOrCreate(
            ['nisn' => '0081234567'],
            ['school_id' => $school->id, 'classroom_id' => $classroom->id, 'name' => 'Nadia Putri', 'address' => 'Jakarta'],
        );

        $accounts = [
            ['name' => 'Yayasan SIMANIS', 'username' => 'yayasan123', 'role' => 'super_admin', 'job_title' => 'Yayasan & Super Admin', 'school_id' => null, 'student_id' => null],
            ['name' => 'Admin SMA Cendekia', 'username' => 'admin123', 'role' => 'school_admin', 'job_title' => 'Kepala sekolah', 'school_id' => $school->id, 'student_id' => null],
            ['name' => 'Orang tua Nadia Putri', 'username' => 'user123', 'role' => 'parent', 'job_title' => null, 'school_id' => $school->id, 'student_id' => $student->id],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['username' => $account['username']],
                [...$account, 'email' => null, 'password' => Hash::make($account['username'])],
            );
        }

        $admin = User::where('username', 'admin123')->firstOrFail();
        Assignment::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Latihan persamaan linear', 'classroom_id' => $classroom->id],
            ['created_by' => $admin->id, 'description' => 'Kerjakan soal nomor 1–10 pada buku latihan.', 'subject' => 'Matematika', 'due_date' => now()->toDateString()],
        );
        Assignment::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Rangkuman bab 3', 'classroom_id' => $classroom->id],
            ['created_by' => $admin->id, 'description' => 'Buat rangkuman teks eksplanasi dalam satu halaman.', 'subject' => 'Bahasa Indonesia', 'due_date' => now()->addDay()->toDateString()],
        );
        Grade::firstOrCreate(
            ['student_id' => $student->id, 'subject' => 'Matematika', 'assessment' => 'Kuis Persamaan Linear'],
            ['school_id' => $school->id, 'created_by' => $admin->id, 'score' => 92],
        );
        StudentNote::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'Selamat datang di SIMANIS'],
            ['school_id' => $school->id, 'created_by' => $admin->id, 'body' => 'Semangat belajar! Catatan yang dibagikan sekolah tampil di sini.', 'visible_to_family' => true],
        );
        Attendance::firstOrCreate(
            ['student_id' => $student->id, 'date' => now()->toDateString()],
            ['school_id' => $school->id, 'recorded_by' => $admin->id, 'status' => 'hadir', 'note' => 'Tepat waktu'],
        );
        Timetable::firstOrCreate(
            ['classroom_id' => $classroom->id, 'weekday' => now()->dayOfWeekIso, 'starts_at' => '08:00:00', 'subject' => 'Matematika'],
            ['school_id' => $school->id, 'created_by' => $admin->id, 'teacher' => 'Ratna Puspitasari', 'ends_at' => '09:20:00', 'room' => 'R. 08'],
        );
        StudentBill::firstOrCreate(
            ['student_id' => $student->id, 'title' => 'Iuran kegiatan sekolah'],
            ['school_id' => $school->id, 'created_by' => $admin->id, 'amount' => 150000, 'due_date' => now()->addDays(12)->toDateString(), 'status' => 'unpaid'],
        );
        SchoolEvent::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Festival Seni Cendekia'],
            ['created_by' => $admin->id, 'description' => 'Panggung kreativitas, pameran seni, dan pertunjukan siswa.', 'starts_at' => now()->addDays(8)->setTime(8, 0), 'location' => 'Aula sekolah'],
        );
        SchoolAnnouncement::firstOrCreate(
            ['school_id' => $school->id, 'title' => 'Jadwal penilaian tengah semester'],
            ['created_by' => $admin->id, 'category' => 'Akademik', 'body' => 'Jadwal penilaian tengah semester telah dibagikan. Silakan persiapkan dirimu dan hubungi wali kelas jika ada pertanyaan.'],
        );
    }
}
