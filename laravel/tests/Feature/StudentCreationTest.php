<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_parent_usernames_must_be_different(): void
    {
        $school = School::create(['name' => 'Sekolah Uji']);
        $admin = User::create([
            'name' => 'Admin Sekolah',
            'username' => 'admin-sekolah',
            'password' => 'password',
            'role' => 'school_admin',
            'school_id' => $school->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Siswa Uji',
            'nisn' => '1234567890',
            'student_username' => 'akun-sama',
            'student_password' => 'password-siswa',
            'parent_username' => 'akun-sama',
            'parent_password' => 'password-orangtua',
        ]);

        $response->assertSessionHasErrors('parent_username');
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('users', 1);
    }
}
