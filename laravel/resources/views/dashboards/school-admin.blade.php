<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#42313A">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/simanis.svg') }}" type="image/svg+xml">
    <title>Dashboard Sekolah — SIMANIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-body">
    @php
        $sidebarRole = 'ADMIN SEKOLAH · ' . $school->name;
        $sidebarGroups = [
            ['title' => 'RINGKASAN', 'items' => [
                ['id' => 'dashboard-overview', 'label' => 'Ikhtisar', 'icon' => '⌂'],
            ]],
            ['title' => 'DATA SEKOLAH', 'items' => [
                ['id' => 'tambah-siswa', 'label' => 'Data siswa', 'icon' => '♙', 'related' => ['daftar-siswa']],
                ['id' => 'buat-kelas', 'label' => 'Kelompok kelas', 'icon' => '▦'],
            ]],
            ['title' => 'AKADEMIK', 'items' => [
                ['id' => 'buat-tugas', 'label' => 'Tugas', 'icon' => '▤'],
                ['id' => 'buat-jadwal', 'label' => 'Jadwal pelajaran', 'icon' => '◷'],
                ['id' => 'input-nilai', 'label' => 'Nilai siswa', 'icon' => '↗', 'related' => ['daftar-nilai']],
            ]],
            ['title' => 'PENDAMPINGAN', 'items' => [
                ['id' => 'catat-presensi', 'label' => 'Presensi', 'icon' => '✓', 'related' => ['daftar-presensi']],
                ['id' => 'tambah-catatan', 'label' => 'Catatan siswa', 'icon' => '✎', 'related' => ['daftar-catatan']],
                ['id' => 'pengumpulan-tugas', 'label' => 'Pengumpulan tugas', 'icon' => '↑'],
            ]],
            ['title' => 'INFORMASI SEKOLAH', 'items' => [
                ['id' => 'buat-tagihan', 'label' => 'Tagihan siswa', 'icon' => 'Rp', 'related' => ['daftar-tagihan']],
                ['id' => 'tambah-kegiatan', 'label' => 'Kegiatan', 'icon' => '✦', 'related' => ['daftar-kegiatan']],
                ['id' => 'tulis-pengumuman', 'label' => 'Pengumuman', 'icon' => '✳', 'related' => ['daftar-pengumuman']],
            ]],
        ];
    @endphp
    @include('dashboards.partials.portal-sidebar')
    <header class="portal-topbar"><button type="button" class="portal-menu-toggle" aria-label="Buka navigasi" aria-controls="portal-sidebar" aria-expanded="true" data-sidebar-toggle><span></span><span></span><span></span></button><a class="simanis-brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark"><span></span><span></span><span></span><span></span></span><span>SIMANIS<small>SISTEM INFORMASI AKADEMIK DAN INFORMASI SISWA</small></span></a><div class="portal-user"><span class="portal-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->job_title }} · {{ $school->name }}</small></span><button type="button" class="install-app-button" data-install-app hidden>Pasang aplikasi</button><form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form></div></header>
    <main class="portal-container">
        <div class="portal-heading" id="dashboard-overview"><div><p class="portal-eyebrow">PANEL ADMINISTRASI SEKOLAH</p><h1>Dashboard {{ $school->name }}</h1><p>Kelola kelas, data siswa, dan kegiatan akademik sekolah dalam satu tempat.</p></div><span class="portal-date">{{ now()->translatedFormat('l, d F Y') }}</span></div>
        @if (session('status')) <div class="portal-alert portal-alert-success">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="portal-alert portal-alert-error">Periksa kembali isian formulir. {{ $errors->first() }}</div> @endif

        <section class="metric-grid">
            <article class="metric-card"><span class="metric-icon">♙</span><small>SISWA</small><strong>{{ $students->count() }}</strong><span>Data siswa di sekolah ini</span></article>
            <article class="metric-card"><span class="metric-icon metric-icon-violet">▦</span><small>KELOMPOK KELAS</small><strong>{{ $classrooms->count() }}</strong><span>Kelompok belajar terdaftar</span></article>
            <article class="metric-card"><span class="metric-icon metric-icon-gold">▤</span><small>TUGAS AKTIF</small><strong>{{ $assignmentCount }}</strong><span>{{ $assignments->sum('submissions_count') }} hasil tugas terkirim pada daftar terbaru</span></article>
        </section>

        <section class="portal-card management-card">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">DATA INDUK</p><h2>Siswa dan kelompok kelas</h2><p class="section-description">Kelola identitas siswa serta akun pribadi siswa dan orang tua.</p></div><span class="soft-count">{{ $students->count() }} siswa</span></div>
            <div class="portal-form-grid">
                <form action="{{ route('admin.students.store') }}" method="POST" class="portal-form" id="tambah-siswa">
                    @csrf
                    <h3>Tambahkan siswa</h3>
                    <label>Nama lengkap<input name="name" maxlength="160" placeholder="Nama sesuai data sekolah" required></label>
                    <div class="portal-form-row"><label>NISN<input name="nisn" maxlength="32" placeholder="Nomor induk siswa nasional" required></label><label>Kelompok kelas<select name="classroom_id"><option value="">Belum ditempatkan</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }} · {{ $classroom->academic_year }}</option>@endforeach</select></label></div>
                    <div class="portal-form-row"><label>Tanggal lahir<input type="date" name="date_of_birth"></label><label>Nomor telepon<input name="phone" maxlength="32"></label></div>
                    <label>Alamat<input name="address" maxlength="255" placeholder="Alamat tempat tinggal"></label>
                    <h4>Akun siswa pribadi</h4>
                    <div class="portal-form-row"><label>Username siswa<input name="student_username" maxlength="100" autocomplete="off" required></label><label>Password siswa<input name="student_password" type="password" minlength="8" autocomplete="new-password" required></label></div>
                    <h4>Akun orang tua (opsional)</h4>
                    <div class="portal-form-row"><label>Username orang tua<input name="parent_username" maxlength="100" autocomplete="off"></label><label>Password orang tua<input name="parent_password" type="password" minlength="8" autocomplete="new-password"></label></div>
                    <button class="portal-button" type="submit">Simpan data siswa</button>
                </form>
                <form action="{{ route('admin.classrooms.store') }}" method="POST" class="portal-form portal-form-tinted" id="buat-kelas">
                    @csrf
                    <h3>Buat kelompok kelas</h3>
                    <label>Nama kelas<input name="name" maxlength="50" placeholder="Contoh: 8B" required></label>
                    <label>Tahun ajaran<input name="academic_year" maxlength="20" placeholder="{{ now()->year }}/{{ now()->addYear()->format('y') }}" required></label>
                    <button class="portal-button portal-button-light" type="submit">Tambah kelas <span>＋</span></button>
                    <div class="classroom-chip-list">@forelse ($classrooms as $classroom)<span class="classroom-chip">{{ $classroom->name }} <small>{{ $classroom->students_count }} siswa</small></span>@empty<span class="muted-copy">Belum ada kelompok kelas.</span>@endforelse</div>
                </form>
            </div>
            <div class="portal-table-wrap" id="daftar-siswa"><table class="portal-table"><thead><tr><th>Nama siswa</th><th>NISN</th><th>Kelompok kelas</th><th>Alamat</th><th>Username siswa</th><th></th></tr></thead><tbody>
                @forelse ($students as $student)<tr><td><strong>{{ $student->name }}</strong></td><td>{{ $student->nisn }}</td><td>{{ $student->classroom?->name ?? 'Belum ditempatkan' }}</td><td>{{ $student->address ?: '—' }}</td><td>{{ $student->accounts->firstWhere('role', 'student')?->username ?? '—' }}</td><td><details class="inline-edit"><summary>Edit</summary><form action="{{ route('admin.students.update', $student->id) }}" method="POST" class="inline-edit-form">@csrf @method('PUT')<label>Nama<input name="name" value="{{ $student->name }}" required></label><label>NISN<input name="nisn" value="{{ $student->nisn }}" required></label><label>Kelas<select name="classroom_id"><option value="">Belum ditempatkan</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected($student->classroom_id === $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></label><label>Tanggal lahir<input name="date_of_birth" type="date" value="{{ $student->date_of_birth?->format('Y-m-d') }}"></label><label>Alamat<input name="address" value="{{ $student->address }}"></label><label>Telepon<input name="phone" value="{{ $student->phone }}"></label><button class="portal-button portal-button-light" type="submit">Simpan perubahan</button></form></details></td></tr>
                @empty<tr><td colspan="6" class="table-empty">Belum ada data siswa. Tambahkan siswa menggunakan formulir di atas.</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="portal-card management-card" id="akademik">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">KEGIATAN AKADEMIK</p><h2>Tugas, jadwal, dan nilai</h2><p class="section-description">Materi yang ditambahkan di sini hanya akan terlihat oleh kelompok siswa yang dipilih.</p></div></div>
            <div class="portal-form-grid three-forms">
                <form action="{{ route('admin.assignments.store') }}" method="POST" class="portal-form" id="buat-tugas">
                    @csrf<h3>Buat tugas</h3>
                    <label>Judul tugas<input name="title" maxlength="160" required></label><label>Mata pelajaran<input name="subject" maxlength="100" required></label>
                    <label>Kelompok kelas<select name="classroom_id"><option value="">Semua kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></label>
                    <label>Tenggat<input name="due_date" type="date"></label><label>Instruksi<textarea name="description" rows="3" maxlength="3000"></textarea></label>
                    <button class="portal-button" type="submit">Simpan tugas</button>
                </form>
                <form action="{{ route('admin.timetables.store') }}" method="POST" class="portal-form" id="buat-jadwal">
                    @csrf<h3>Tambah jadwal pelajaran</h3>
                    <label>Kelompok kelas<select name="classroom_id" required><option value="">Pilih kelas</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></label>
                    <label>Mata pelajaran<input name="subject" maxlength="100" required></label><label>Nama guru<input name="teacher" maxlength="160"></label>
                    <label>Hari<select name="weekday" required><option value="">Pilih hari</option><option value="1">Senin</option><option value="2">Selasa</option><option value="3">Rabu</option><option value="4">Kamis</option><option value="5">Jumat</option><option value="6">Sabtu</option><option value="7">Minggu</option></select></label>
                    <div class="portal-form-row"><label>Mulai<input name="starts_at" type="time" required></label><label>Selesai<input name="ends_at" type="time" required></label></div>
                    <label>Ruangan<input name="room" maxlength="50"></label><button class="portal-button" type="submit">Simpan jadwal</button>
                </form>
                <form action="{{ route('admin.grades.store') }}" method="POST" class="portal-form" id="input-nilai">
                    @csrf<h3>Input nilai</h3>
                    <label>Siswa<select name="student_id" required><option value="">Pilih siswa</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }} · {{ $student->nisn }}</option>@endforeach</select></label>
                    <label>Mata pelajaran<input name="subject" maxlength="100" required></label><label>Jenis penilaian<input name="assessment" maxlength="160" placeholder="Contoh: Kuis bab 2" required></label>
                    <label>Nilai (0–100)<input name="score" type="number" min="0" max="100" step="0.01" required></label><button class="portal-button" type="submit">Simpan nilai</button>
                </form>
            </div>
            <div class="portal-table-wrap" id="daftar-nilai"><table class="portal-table"><thead><tr><th>Siswa</th><th>Mata pelajaran</th><th>Penilaian</th><th>Nilai</th><th>Tanggal</th></tr></thead><tbody>
                @forelse ($grades as $grade)<tr><td><strong>{{ $grade->student->name }}</strong></td><td>{{ $grade->subject }}</td><td>{{ $grade->assessment }}</td><td><span class="role-pill">{{ number_format($grade->score, 2) }}</span></td><td>{{ $grade->created_at->format('d M Y') }}</td></tr>@empty<tr><td colspan="5" class="table-empty">Nilai yang diinput akan tercatat di sini.</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="portal-card management-card" id="presensi-catatan">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">PENDAMPINGAN SISWA</p><h2>Presensi dan catatan siswa</h2></div></div>
            <div class="portal-form-grid">
                <form action="{{ route('admin.attendances.store') }}" method="POST" class="portal-form" id="catat-presensi">
                    @csrf<h3>Catat presensi</h3>
                    <label>Siswa<select name="student_id" required><option value="">Pilih siswa</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></label>
                    <div class="portal-form-row"><label>Tanggal<input name="date" type="date" value="{{ now()->toDateString() }}" required></label><label>Status<select name="status" required><option value="hadir">Hadir</option><option value="izin">Izin</option><option value="sakit">Sakit</option><option value="alpa">Alpa</option></select></label></div>
                    <label>Catatan (opsional)<input name="note" maxlength="255"></label><button class="portal-button" type="submit">Simpan presensi</button>
                </form>
                <form action="{{ route('admin.notes.store') }}" method="POST" class="portal-form portal-form-tinted" id="tambah-catatan">
                    @csrf<h3>Tambah catatan siswa</h3>
                    <label>Siswa<select name="student_id" required><option value="">Pilih siswa</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></label>
                    <label>Judul catatan<input name="title" maxlength="160" required></label><label>Isi catatan<textarea name="body" rows="4" maxlength="3000" required></textarea></label>
                    <label class="checkbox-label"><input type="checkbox" name="visible_to_family" value="1" checked> Bagikan catatan ini kepada siswa dan orang tua</label><button class="portal-button" type="submit">Simpan catatan</button>
                </form>
            </div>
            <div class="portal-columns portal-columns-inner">
                <div class="portal-table-wrap" id="daftar-presensi"><table class="portal-table"><thead><tr><th>Tanggal</th><th>Siswa</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
                    @forelse ($attendances as $attendance)<tr><td>{{ $attendance->date->format('d M Y') }}</td><td><strong>{{ $attendance->student->name }}</strong></td><td><span class="role-pill">{{ ucfirst($attendance->status) }}</span></td><td>{{ $attendance->note ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="table-empty">Belum ada presensi tercatat.</td></tr>@endforelse
                </tbody></table></div>
                <div class="portal-table-wrap" id="daftar-catatan"><table class="portal-table"><thead><tr><th>Catatan</th><th>Siswa</th><th>Akses</th></tr></thead><tbody>
                    @forelse ($notes as $note)<tr><td><strong>{{ $note->title }}</strong></td><td>{{ $note->student->name }}</td><td>{{ $note->visible_to_family ? 'Dibagikan' : 'Internal sekolah' }}</td></tr>@empty<tr><td colspan="3" class="table-empty">Belum ada catatan siswa.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </section>

        <section class="portal-card management-card" id="pengumpulan-tugas">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">HASIL BELAJAR MANDIRI</p><h2>Pengumpulan tugas siswa</h2><p class="section-description">Berkas tersimpan secara privat dan hanya tersedia bagi sekolah terkait.</p></div></div>
            <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Siswa</th><th>Tugas</th><th>Nama berkas</th><th>Waktu dikumpulkan</th><th></th></tr></thead><tbody>
                @forelse ($submissions as $submission)<tr><td><strong>{{ $submission->student->name }}</strong></td><td>{{ $submission->assignment->title }}</td><td>{{ $submission->original_name }}</td><td>{{ $submission->submitted_at->format('d M Y, H:i') }}</td><td><a class="table-action" href="{{ route('admin.submissions.download', $submission) }}">Unduh berkas</a></td></tr>@empty<tr><td colspan="5" class="table-empty">Hasil tugas yang dikirim siswa akan tercatat di sini.</td></tr>@endforelse
            </tbody></table>            </div>
        </section>

        <section class="portal-card management-card" id="informasi-sekolah">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">INFORMASI SEKOLAH</p><h2>Tagihan, kegiatan, dan pengumuman</h2><p class="section-description">Bagikan informasi sekolah serta rincian tagihan pribadi kepada keluarga siswa.</p></div></div>
            <div class="portal-form-grid three-forms">
                <form action="{{ route('admin.bills.store') }}" method="POST" class="portal-form" id="buat-tagihan">
                    @csrf<h3>Buat tagihan siswa</h3>
                    <label>Siswa<select name="student_id" required><option value="">Pilih siswa</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }} · {{ $student->nisn }}</option>@endforeach</select></label>
                    <label>Nama tagihan<input name="title" maxlength="160" placeholder="Contoh: Buku pendamping" required></label>
                    <label>Jumlah (rupiah)<input name="amount" type="number" min="1" max="1000000000" required></label>
                    <label>Tanggal jatuh tempo<input name="due_date" type="date"></label><button class="portal-button" type="submit">Terbitkan tagihan</button>
                </form>
                <form action="{{ route('admin.events.store') }}" method="POST" class="portal-form" id="tambah-kegiatan">
                    @csrf<h3>Tambah kegiatan</h3>
                    <label>Nama kegiatan<input name="title" maxlength="160" required></label>
                    <label>Waktu mulai<input name="starts_at" type="datetime-local" required></label>
                    <label>Lokasi<input name="location" maxlength="160"></label>
                    <label>Informasi kegiatan<textarea name="description" rows="3" maxlength="3000"></textarea></label><button class="portal-button" type="submit">Bagikan kegiatan</button>
                </form>
                <form action="{{ route('admin.announcements.store') }}" method="POST" class="portal-form" id="tulis-pengumuman">
                    @csrf<h3>Tulis pengumuman</h3>
                    <label>Judul pengumuman<input name="title" maxlength="160" required></label>
                    <label>Kategori<select name="category" required><option>Akademik</option><option>Kegiatan</option><option>Informasi</option><option>Fasilitas</option></select></label>
                    <label>Isi pengumuman<textarea name="body" rows="5" maxlength="3000" required></textarea></label><button class="portal-button" type="submit">Terbitkan pengumuman</button>
                </form>
            </div>
            <div class="portal-columns portal-columns-inner">
                <div class="portal-table-wrap" id="daftar-tagihan"><table class="portal-table"><thead><tr><th>Tagihan</th><th>Siswa</th><th>Jumlah</th><th>Jatuh tempo</th></tr></thead><tbody>
                    @forelse ($bills as $bill)<tr><td><strong>{{ $bill->title }}</strong></td><td>{{ $bill->student->name }}</td><td>Rp {{ number_format($bill->amount, 0, ',', '.') }}</td><td>{{ $bill->due_date?->format('d M Y') ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="table-empty">Tagihan siswa akan tampil di sini.</td></tr>@endforelse
                </tbody></table></div>
                <div class="portal-table-wrap" id="daftar-kegiatan"><table class="portal-table"><thead><tr><th>Kegiatan sekolah</th><th>Waktu</th><th>Lokasi</th></tr></thead><tbody>
                    @forelse ($events as $event)<tr><td><strong>{{ $event->title }}</strong></td><td>{{ $event->starts_at->format('d M Y, H:i') }}</td><td>{{ $event->location ?: '—' }}</td></tr>@empty<tr><td colspan="3" class="table-empty">Kegiatan sekolah akan tampil di sini.</td></tr>@endforelse
                </tbody></table></div>
            </div>
            <div class="portal-table-wrap" id="daftar-pengumuman"><table class="portal-table"><thead><tr><th>Pengumuman</th><th>Kategori</th><th>Diterbitkan</th></tr></thead><tbody>
                @forelse ($announcements as $announcement)<tr><td><strong>{{ $announcement->title }}</strong></td><td>{{ $announcement->category }}</td><td>{{ $announcement->created_at->format('d M Y') }}</td></tr>@empty<tr><td colspan="3" class="table-empty">Pengumuman sekolah akan tampil di sini.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <footer class="portal-footer">© {{ now()->year }} SIMANIS <span>Dashboard khusus {{ $school->name }}</span></footer>
    </main>
</body>
</html>
