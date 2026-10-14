<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SimanisPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_simanis_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('SIMANIS')
            ->assertSee('SISTEM INFORMASI AKADEMIK DAN INFORMASI SISWA')
            ->assertSee('yayasan123')
            ->assertSee('admin123')
            ->assertSee('user123');
    }

    public function test_login_page_exposes_installable_app_manifest(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('data-install-app', false);

        $manifest = json_decode(
            file_get_contents(public_path('manifest.webmanifest')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertFileExists(public_path('icons/simanis.svg'));
    }

    public function test_demo_account_signs_in_to_its_own_role_dashboard(): void
    {
        $this->seed();

        $this->post(route('login.store'), [
            'username' => 'yayasan123',
            'password' => 'yayasan123',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertRedirect(route('foundation.dashboard'));
        $this->get(route('foundation.dashboard'))->assertOk()->assertSee('Ikhtisar SIMANIS')->assertSee('SMA Cendekia');
    }

    public function test_family_portal_shows_only_the_linked_students_personal_records(): void
    {
        [$school, $classroom] = $this->createSchoolAndClassroom('Sekolah A', '8A');
        $ownStudent = Student::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'nisn' => '1000000001',
            'name' => 'Murid Pribadi',
        ]);
        $sameSchoolStudent = Student::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'nisn' => '1000000009',
            'name' => 'Murid Sekelas',
        ]);
        $otherSchool = School::create(['name' => 'Sekolah B']);
        $otherClassroom = Classroom::create(['school_id' => $otherSchool->id, 'name' => '8Z', 'academic_year' => '2026/27']);
        $otherStudent = Student::create([
            'school_id' => $otherSchool->id,
            'classroom_id' => $otherClassroom->id,
            'nisn' => '2000000002',
            'name' => 'Murid Rahasia',
        ]);
        Grade::create([
            'school_id' => $school->id,
            'student_id' => $ownStudent->id,
            'created_by' => $this->createSchoolAdmin($school)->id,
            'subject' => 'Matematika',
            'assessment' => 'Kuis',
            'score' => 95,
        ]);
        Grade::create([
            'school_id' => $otherSchool->id,
            'student_id' => $otherStudent->id,
            'created_by' => $this->createSchoolAdmin($otherSchool)->id,
            'subject' => 'Rahasia',
            'assessment' => 'Rahasia',
            'score' => 20,
        ]);
        Grade::create([
            'school_id' => $school->id,
            'student_id' => $sameSchoolStudent->id,
            'created_by' => $this->createSchoolAdmin($school)->id,
            'subject' => 'Nilai Privat Sekelas',
            'assessment' => 'Penilaian lain',
            'score' => 10,
        ]);
        StudentNote::create([
            'school_id' => $school->id,
            'student_id' => $ownStudent->id,
            'created_by' => $this->createSchoolAdmin($school)->id,
            'title' => 'Catatan internal',
            'body' => 'Hanya dapat dibaca staf sekolah.',
            'visible_to_family' => false,
        ]);
        $parent = User::factory()->create([
            'name' => 'Wali Murid Pribadi',
            'username' => 'wali-pribadi',
            'password' => 'password123',
            'role' => 'parent',
            'school_id' => $school->id,
            'student_id' => $ownStudent->id,
        ]);

        $this->actingAs($parent)
            ->get(route('family.dashboard'))
            ->assertOk()
            ->assertSee('Murid Pribadi')
            ->assertSee('Matematika')
            ->assertDontSee('Murid Rahasia')
            ->assertDontSee('Sekolah B')
            ->assertDontSee('Murid Sekelas')
            ->assertDontSee('Nilai Privat Sekelas')
            ->assertDontSee('Catatan internal');

        $this->actingAs($parent)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($parent)->get(route('foundation.dashboard'))->assertForbidden();
    }

    public function test_school_admin_cannot_write_records_for_another_school(): void
    {
        [$school, $classroom] = $this->createSchoolAndClassroom('Sekolah A', '8A');
        [$otherSchool, $otherClassroom] = $this->createSchoolAndClassroom('Sekolah B', '9B');
        $otherStudent = Student::create([
            'school_id' => $otherSchool->id,
            'classroom_id' => $otherClassroom->id,
            'nisn' => '3000000003',
            'name' => 'Siswa Sekolah Lain',
        ]);
        $admin = $this->createSchoolAdmin($school);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kelompok kelas')
            ->assertSee('Buat tugas')
            ->assertSee('Catat presensi');

        $this->actingAs($admin)
            ->post(route('admin.grades.store'), [
                'student_id' => $otherStudent->id,
                'subject' => 'Matematika',
                'assessment' => 'Kuis',
                'score' => 88,
            ])
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseMissing('grades', ['student_id' => $otherStudent->id]);
        $this->actingAs($admin)->get(route('foundation.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('family.dashboard'))->assertForbidden();
    }

    public function test_school_admin_can_create_a_student_with_individual_accounts(): void
    {
        [$school, $classroom] = $this->createSchoolAndClassroom('Sekolah Akun', '7A');
        $admin = $this->createSchoolAdmin($school);

        $this->actingAs($admin)
            ->post(route('admin.students.store'), [
                'name' => 'Siswa Baru',
                'nisn' => '5000000005',
                'classroom_id' => $classroom->id,
                'address' => 'Jalan Belajar 1',
                'student_username' => 'siswa-baru',
                'student_password' => 'password-siswa',
                'parent_username' => 'wali-baru',
                'parent_password' => 'password-wali',
            ])
            ->assertSessionHasNoErrors();

        $student = Student::where('nisn', '5000000005')->firstOrFail();
        $this->put(route('admin.students.update', $student->id), [
            'name' => 'Siswa Baru Diperbarui',
            'nisn' => '5000000005',
            'classroom_id' => $classroom->id,
            'address' => 'Jalan Belajar 2',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Siswa Baru Diperbarui',
            'address' => 'Jalan Belajar 2',
        ]);
        $studentAccount = User::where('username', 'siswa-baru')->firstOrFail();
        $parentAccount = User::where('username', 'wali-baru')->firstOrFail();
        $this->assertSame($student->id, $studentAccount->student_id);
        $this->assertSame($student->id, $parentAccount->student_id);
        $this->assertSame($school->id, $studentAccount->school_id);
        $this->assertTrue(password_verify('password-siswa', $studentAccount->getAuthPassword()));
        $this->assertTrue(password_verify('password-wali', $parentAccount->getAuthPassword()));

        $this->post(route('logout'));
        $this->post(route('login.store'), ['username' => 'siswa-baru', 'password' => 'password-siswa'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('family.dashboard'));
        $this->get(route('family.dashboard'))->assertOk()->assertSee('Siswa Baru Diperbarui');
    }

    public function test_school_deactivation_revokes_existing_access_and_blocks_future_logins(): void
    {
        [$school] = $this->createSchoolAndClassroom('Sekolah Dinonaktifkan', '6A');
        $admin = User::factory()->create([
            'username' => 'admin-nonaktif',
            'password' => 'password-sekolah',
            'role' => 'school_admin',
            'school_id' => $school->id,
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $school->update(['status' => 'inactive']);
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.store'), [
            'username' => 'admin-nonaktif',
            'password' => 'password-sekolah',
        ])->assertSessionHasErrors('username');
    }

    public function test_school_can_publish_private_academic_records_and_school_notices(): void
    {
        [$school, $classroom] = $this->createSchoolAndClassroom('Sekolah Aktif', '8B');
        $student = Student::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'nisn' => '6000000006',
            'name' => 'Siswa Terhubung',
        ]);
        $admin = $this->createSchoolAdmin($school);
        $this->actingAs($admin);

        $this->post(route('admin.assignments.store'), [
            'classroom_id' => $classroom->id,
            'title' => 'Tugas kelas',
            'subject' => 'IPA',
            'description' => 'Laporan praktikum.',
            'due_date' => now()->addDay()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.timetables.store'), [
            'classroom_id' => $classroom->id,
            'subject' => 'IPA',
            'teacher' => 'Ibu Guru',
            'weekday' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'room' => 'Lab 1',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.grades.store'), [
            'student_id' => $student->id,
            'subject' => 'IPA',
            'assessment' => 'Ulangan bab 1',
            'score' => 91,
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.attendances.store'), [
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => 'hadir',
            'note' => 'Tepat waktu',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.notes.store'), [
            'student_id' => $student->id,
            'title' => 'Catatan wali kelas',
            'body' => 'Perkembangan belajar sangat baik.',
            'visible_to_family' => '1',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.bills.store'), [
            'student_id' => $student->id,
            'title' => 'Iuran kelas',
            'amount' => 75000,
            'due_date' => now()->addDays(7)->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.events.store'), [
            'title' => 'Pekan Literasi',
            'description' => 'Membaca bersama di perpustakaan.',
            'starts_at' => now()->addDays(3)->format('Y-m-d\\TH:i'),
            'location' => 'Perpustakaan',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.announcements.store'), [
            'title' => 'Informasi ujian',
            'category' => 'Akademik',
            'body' => 'Ujian dimulai minggu depan.',
        ])->assertSessionHasNoErrors();

        $parent = User::factory()->create([
            'username' => 'wali-sekolah-aktif',
            'role' => 'parent',
            'school_id' => $school->id,
            'student_id' => $student->id,
        ]);
        $this->actingAs($parent)
            ->get(route('family.dashboard'))
            ->assertOk()
            ->assertSee('Ulangan bab 1')
            ->assertSee('Catatan wali kelas')
            ->assertSee('Iuran kelas')
            ->assertSee('Pekan Literasi')
            ->assertSee('Informasi ujian')
            ->assertSee('Laporan praktikum.');
    }

    public function test_student_can_submit_a_file_only_to_an_assignment_for_their_class(): void
    {
        Storage::fake('local');
        [$school, $classroom] = $this->createSchoolAndClassroom('Sekolah A', '8A');
        $student = Student::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'nisn' => '4000000004',
            'name' => 'Siswa Pribadi',
        ]);
        $admin = $this->createSchoolAdmin($school);
        $account = User::factory()->create([
            'username' => 'murid-pribadi',
            'role' => 'student',
            'school_id' => $school->id,
            'student_id' => $student->id,
        ]);
        $assignment = Assignment::create([
            'school_id' => $school->id,
            'classroom_id' => $classroom->id,
            'created_by' => $admin->id,
            'title' => 'Laporan IPA',
            'subject' => 'IPA',
            'due_date' => now()->addDay()->toDateString(),
        ]);
        [$otherSchool, $otherClassroom] = $this->createSchoolAndClassroom('Sekolah C', '7C');
        $otherAssignment = Assignment::create([
            'school_id' => $otherSchool->id,
            'classroom_id' => $otherClassroom->id,
            'created_by' => $this->createSchoolAdmin($otherSchool)->id,
            'title' => 'Tugas rahasia',
            'subject' => 'IPA',
        ]);
        $otherClassroomInSameSchool = Classroom::create([
            'school_id' => $school->id,
            'name' => '8B',
            'academic_year' => '2026/27',
        ]);
        $differentClassAssignment = Assignment::create([
            'school_id' => $school->id,
            'classroom_id' => $otherClassroomInSameSchool->id,
            'created_by' => $admin->id,
            'title' => 'Tugas kelas lain',
            'subject' => 'IPA',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($account)
            ->post(route('family.assignments.submit', $assignment), [
                'submission_file' => UploadedFile::fake()->create('hasil.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', ['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        $this->assertCount(1, Storage::disk('local')->allFiles('submissions/'.$student->id));

        $this->actingAs($account)->post(route('family.assignments.submit', $otherAssignment), [
            'submission_file' => UploadedFile::fake()->create('lain.pdf', 20, 'application/pdf'),
        ])->assertNotFound();

        $this->actingAs($account)->post(route('family.assignments.submit', $differentClassAssignment), [
            'submission_file' => UploadedFile::fake()->create('kelas-lain.pdf', 20, 'application/pdf'),
        ])->assertNotFound();
    }

    private function createSchoolAndClassroom(string $schoolName, string $classroomName): array
    {
        $school = School::create(['name' => $schoolName]);
        $classroom = Classroom::create([
            'school_id' => $school->id,
            'name' => $classroomName,
            'academic_year' => '2026/27',
        ]);

        return [$school, $classroom];
    }

    private function createSchoolAdmin(School $school): User
    {
        return User::factory()->create([
            'role' => 'school_admin',
            'school_id' => $school->id,
            'job_title' => 'Guru',
        ]);
    }
}
