<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyPortalController;
use App\Http\Controllers\FoundationController;
use App\Http\Controllers\SchoolAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('yayasan')->name('foundation.')->middleware('role:super_admin')->group(function () {
        Route::get('/dashboard', [FoundationController::class, 'index'])->name('dashboard');
        Route::post('/sekolah', [FoundationController::class, 'storeSchool'])->name('schools.store');
        Route::put('/sekolah/{school}', [FoundationController::class, 'updateSchool'])->name('schools.update');
        Route::post('/administrator', [FoundationController::class, 'storeAdministrator'])->name('administrators.store');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:school_admin')->group(function () {
        Route::get('/dashboard', [SchoolAdminController::class, 'index'])->name('dashboard');
        Route::post('/kelas', [SchoolAdminController::class, 'storeClassroom'])->name('classrooms.store');
        Route::post('/siswa', [SchoolAdminController::class, 'storeStudent'])->name('students.store');
        Route::put('/siswa/{studentId}', [SchoolAdminController::class, 'updateStudent'])->whereNumber('studentId')->name('students.update');
        Route::post('/tugas', [SchoolAdminController::class, 'storeAssignment'])->name('assignments.store');
        Route::post('/jadwal', [SchoolAdminController::class, 'storeTimetable'])->name('timetables.store');
        Route::post('/nilai', [SchoolAdminController::class, 'storeGrade'])->name('grades.store');
        Route::post('/presensi', [SchoolAdminController::class, 'storeAttendance'])->name('attendances.store');
        Route::post('/catatan', [SchoolAdminController::class, 'storeNote'])->name('notes.store');
        Route::post('/tagihan', [SchoolAdminController::class, 'storeBill'])->name('bills.store');
        Route::post('/kegiatan', [SchoolAdminController::class, 'storeEvent'])->name('events.store');
        Route::post('/pengumuman', [SchoolAdminController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::get('/pengumpulan/{submission}', [SchoolAdminController::class, 'downloadSubmission'])->name('submissions.download');
    });

    Route::prefix('portal')->name('family.')->middleware('role:student,parent')->group(function () {
        Route::get('/dashboard', [FamilyPortalController::class, 'index'])->name('dashboard');
        Route::post('/tugas/{assignment}', [FamilyPortalController::class, 'submit'])->name('assignments.submit');
    });
});
