<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#42313A">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/simanis.svg') }}" type="image/svg+xml">
    <title>Dashboard Pribadi — SIMANIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-body">
    @php
        $sidebarRole = auth()->user()->role === 'parent' ? 'ORANG TUA / WALI' : 'SISWA';
        $sidebarGroups = [
            ['title' => 'RINGKASAN', 'items' => [
                ['id' => 'dashboard-overview', 'label' => 'Beranda', 'icon' => '⌂'],
            ]],
            ['title' => 'PERKEMBANGAN BELAJAR', 'items' => [
                ['id' => 'nilai-siswa', 'label' => 'Nilai siswa', 'icon' => '↗'],
                ['id' => 'tugas-pengumpulan', 'label' => 'Tugas dan pengumpulan', 'icon' => '▤'],
                ['id' => 'jadwal-pelajaran', 'label' => 'Jadwal pelajaran', 'icon' => '◷'],
                ['id' => 'presensi-terbaru', 'label' => 'Presensi', 'icon' => '✓'],
            ]],
            ['title' => 'INFORMASI SEKOLAH', 'items' => [
                ['id' => 'tagihan-sekolah', 'label' => 'Tagihan sekolah', 'icon' => 'Rp'],
                ['id' => 'pengumuman-sekolah', 'label' => 'Pengumuman', 'icon' => '✳'],
                ['id' => 'kegiatan-mendatang', 'label' => 'Kegiatan mendatang', 'icon' => '✦'],
                ['id' => 'catatan-keluarga', 'label' => 'Catatan wali kelas', 'icon' => '✎'],
            ]],
        ];
    @endphp
    @include('dashboards.partials.portal-sidebar')
    <header class="portal-topbar"><button type="button" class="portal-menu-toggle" aria-label="Buka navigasi" aria-controls="portal-sidebar" aria-expanded="true" data-sidebar-toggle><span></span><span></span><span></span></button><a class="simanis-brand" href="{{ route('family.dashboard') }}"><span class="brand-mark"><span></span><span></span><span></span><span></span></span><span>SIMANIS<small>SISTEM INFORMASI AKADEMIK DAN INFORMASI SISWA</small></span></a><div class="portal-user"><span class="portal-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'parent' ? 'Orang tua / wali' : 'Siswa' }} · {{ $student->school->name }}</small></span><button type="button" class="install-app-button" data-install-app hidden>Pasang aplikasi</button><form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form></div></header>
    <main class="portal-container">
        <div class="portal-heading" id="dashboard-overview"><div><p class="portal-eyebrow">RUANG BELAJAR PRIBADI · {{ $student->school->name }}</p><h1>Halo, {{ auth()->user()->role === 'parent' ? 'keluarga ' : '' }}{{ $student->name }} <span class="heading-spark">✳</span></h1><p>Informasi ini hanya dapat dilihat oleh akun siswa dan orang tua yang terhubung.</p></div><span class="portal-date">{{ now()->translatedFormat('l, d F Y') }}</span></div>
        @if (session('status')) <div class="portal-alert portal-alert-success">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="portal-alert portal-alert-error">Berkas belum berhasil dikirim. {{ $errors->first() }}</div> @endif

        <section class="family-welcome">
            <div><span class="portal-eyebrow">PROFIL SISWA</span><h2>{{ $student->name }}</h2><p>{{ $student->school->name }} <span>·</span> {{ $student->classroom?->name ?? 'Kelas belum ditetapkan' }} <span>·</span> NISN {{ $student->nisn }}</p></div>
            <div class="family-welcome-mark">SIMANIS<span>✳</span></div>
        </section>
        @php($pendingAssignments = $assignments->filter(fn ($assignment) => $assignment->submissions->isEmpty())->count())
        <section class="metric-grid family-metrics">
            <article class="metric-card"><span class="metric-icon">✓</span><small>KEHADIRAN</small><strong>{{ $attendanceSummary['hadir'] }}</strong><span>Hari hadir tercatat</span></article>
            <article class="metric-card"><span class="metric-icon metric-icon-violet">↗</span><small>RATA-RATA NILAI</small><strong>{{ number_format((float) $grades->avg('score'), 1) }}</strong><span>{{ $grades->count() }} penilaian tercatat</span></article>
            <article class="metric-card"><span class="metric-icon metric-icon-gold">▤</span><small>TUGAS AKTIF</small><strong>{{ $pendingAssignments }}</strong><span>{{ $assignments->count() }} tugas dibagikan</span></article>
            <article class="metric-card"><span class="metric-icon metric-icon-rose">Rp</span><small>TAGIHAN BELUM LUNAS</small><strong>{{ $bills->where('status', 'unpaid')->count() }}</strong><span>Rincian khusus siswa ini</span></article>
        </section>

        <section class="portal-card personal-card" id="nilai-siswa">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">PERKEMBANGAN BELAJAR</p><h2>Nilai siswa</h2></div><span class="soft-count">{{ $grades->count() }} penilaian</span></div>
            <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Mata pelajaran</th><th>Jenis penilaian</th><th>Nilai</th><th>Tanggal</th></tr></thead><tbody>
                @forelse ($grades as $grade)<tr><td><strong>{{ $grade->subject }}</strong></td><td>{{ $grade->assessment }}</td><td><span class="role-pill">{{ number_format($grade->score, 2) }}</span></td><td>{{ $grade->created_at->format('d M Y') }}</td></tr>@empty<tr><td colspan="4" class="table-empty">Nilai yang dibagikan sekolah akan tampil di sini.</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="portal-card personal-card" id="tugas-pengumpulan">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">BELAJAR MANDIRI</p><h2>Tugas dan pengumpulan</h2><p class="section-description">Unggah berkas tugas untuk dikirim ke sekolah. PDF, Word, JPG, atau PNG hingga 10 MB.</p></div></div>
            <div class="assignment-grid">
                @forelse ($assignments as $assignment)
                    @php($submission = $assignment->submissions->first())
                    <article class="family-assignment">
                        <div class="assignment-card-top"><span class="metric-icon">▤</span><span class="role-pill">{{ $assignment->due_date?->translatedFormat('d M Y') ?? 'Tanpa tenggat' }}</span></div>
                        <h3>{{ $assignment->title }}</h3><p class="assignment-subject">{{ $assignment->subject }}{{ $assignment->classroom ? ' · ' . $assignment->classroom->name : '' }}</p>
                        @if ($assignment->description)<p class="assignment-description">{{ $assignment->description }}</p>@endif
                        @if ($submission)
                            <div class="submission-receipt"><span>✓</span><span><strong>Sudah dikumpulkan</strong><small>{{ $submission->original_name }} · {{ $submission->submitted_at->format('d M, H:i') }}</small></span></div>
                        @endif
                        <form action="{{ route('family.assignments.submit', $assignment) }}" method="POST" enctype="multipart/form-data" class="upload-form">
                            @csrf<label for="submission-{{ $assignment->id }}">{{ $submission ? 'Ganti berkas tugas' : 'Pilih berkas hasil tugas' }}</label>
                            <input id="submission-{{ $assignment->id }}" type="file" name="submission_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                            <button type="submit" class="portal-button portal-button-light">{{ $submission ? 'Unggah ulang' : 'Kirim tugas' }} <span>↑</span></button>
                        </form>
                    </article>
                @empty
                    <div class="table-empty">Belum ada tugas yang dibagikan kepada kelompok kelas ini.</div>
                @endforelse
            </div>
        </section>

        <section class="portal-columns family-columns">
            <article class="portal-card personal-card" id="tagihan-sekolah">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">ADMINISTRASI PRIBADI</p><h2>Tagihan sekolah</h2></div><span class="soft-count">{{ $bills->where('status', 'unpaid')->count() }} belum lunas</span></div>
                <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Rincian</th><th>Jumlah</th><th>Jatuh tempo</th><th>Status</th></tr></thead><tbody>
                    @forelse ($bills as $bill)<tr><td><strong>{{ $bill->title }}</strong></td><td>Rp {{ number_format($bill->amount, 0, ',', '.') }}</td><td>{{ $bill->due_date?->format('d M Y') ?? '—' }}</td><td><span class="role-pill {{ $bill->status === 'unpaid' ? 'pill-warning' : '' }}">{{ $bill->status === 'unpaid' ? 'Belum dibayar' : 'Lunas' }}</span></td></tr>@empty<tr><td colspan="4" class="table-empty">Tidak ada tagihan untuk akun ini.</td></tr>@endforelse
                </tbody></table></div>
            </article>
            <article class="portal-card personal-card" id="pengumuman-sekolah">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">KABAR SEKOLAH</p><h2>Pengumuman terbaru</h2></div><span class="soft-count">{{ $announcements->count() }} informasi</span></div>
                <div class="family-notes">
                    @forelse ($announcements as $announcement)<article class="family-note"><span class="note-mark">✳</span><div><span class="announcement-category">{{ $announcement->category }} · {{ $announcement->created_at->format('d M') }}</span><h3>{{ $announcement->title }}</h3><p>{{ $announcement->body }}</p></div></article>@empty<div class="table-empty">Belum ada pengumuman dari sekolah.</div>@endforelse
                </div>
            </article>
        </section>

        <section class="portal-card personal-card" id="kegiatan-mendatang">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">AKTIVITAS SEKOLAH</p><h2>Kegiatan mendatang</h2></div><span class="soft-count">{{ $events->count() }} kegiatan</span></div>
            <div class="event-grid">
                @forelse ($events as $event)<article class="event-card"><span class="metric-icon">✦</span><h3>{{ $event->title }}</h3><p>{{ $event->description ?: 'Informasi kegiatan sekolah.' }}</p><div><span>{{ $event->starts_at->translatedFormat('l, d F · H:i') }}</span><strong>{{ $event->location ?: 'Sekolah' }}</strong></div></article>@empty<div class="table-empty">Belum ada kegiatan mendatang dari sekolah.</div>@endforelse
            </div>
        </section>

        <section class="portal-columns family-columns">
            <article class="portal-card personal-card" id="jadwal-pelajaran">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">WAKTU BELAJAR</p><h2>Jadwal pelajaran</h2></div></div>
                <div class="personal-timetable">
                    @forelse ($timetables as $lesson)
                        @php($weekdayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'])
                        <div class="personal-timetable-row"><span class="day-marker">{{ $weekdayNames[$lesson->weekday] }}</span><span><strong>{{ $lesson->subject }}</strong><small>{{ $lesson->teacher ?: 'Guru mata pelajaran' }} · {{ $lesson->room ?: 'Ruangan belum ditentukan' }}</small></span><time>{{ substr($lesson->starts_at, 0, 5) }}–{{ substr($lesson->ends_at, 0, 5) }}</time></div>
                    @empty
                        <div class="table-empty">Jadwal kelas yang dibagikan sekolah akan tampil di sini.</div>
                    @endforelse
                </div>
            </article>
            <article class="portal-card personal-card" id="catatan-keluarga">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">INFORMASI WALI KELAS</p><h2>Catatan untuk keluarga</h2></div></div>
                <div class="family-notes">
                    @forelse ($notes as $note)
                        <article class="family-note"><span class="note-mark">✳</span><div><h3>{{ $note->title }}</h3><p>{{ $note->body }}</p><time>{{ $note->created_at->format('d M Y') }}</time></div></article>
                    @empty
                        <div class="table-empty">Belum ada catatan yang dibagikan kepada keluarga.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="portal-card personal-card" id="presensi-terbaru">
            <div class="portal-section-heading"><div><p class="portal-eyebrow">RINGKASAN KEHADIRAN</p><h2>Presensi terbaru</h2></div></div>
            <div class="attendance-chips"><span>Hadir <strong>{{ $attendanceSummary['hadir'] }}</strong></span><span>Izin <strong>{{ $attendanceSummary['izin'] }}</strong></span><span>Sakit <strong>{{ $attendanceSummary['sakit'] }}</strong></span><span>Alpa <strong>{{ $attendanceSummary['alpa'] }}</strong></span></div>
            <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Tanggal</th><th>Status</th><th>Catatan</th></tr></thead><tbody>
                @forelse ($attendances as $attendance)<tr><td>{{ $attendance->date->translatedFormat('l, d M Y') }}</td><td><span class="role-pill">{{ ucfirst($attendance->status) }}</span></td><td>{{ $attendance->note ?: '—' }}</td></tr>@empty<tr><td colspan="3" class="table-empty">Presensi yang dicatat sekolah akan tampil di sini.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <footer class="portal-footer">© {{ now()->year }} SIMANIS <span>Ruang belajar pribadi · {{ $student->school->name }}</span></footer>
    </main>
</body>
</html>
