<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#42313A">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/simanis.svg') }}" type="image/svg+xml">
    <title>Yayasan & Super Admin — SIMANIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-body">
    @php
        $sidebarRole = 'SUPER ADMIN YAYASAN';
        $sidebarGroups = [
            ['title' => 'RINGKASAN', 'items' => [
                ['id' => 'dashboard-overview', 'label' => 'Ikhtisar', 'icon' => '⌂'],
            ]],
            ['title' => 'MANAJEMEN SEKOLAH', 'items' => [
                ['id' => 'sekolah-terdaftar', 'label' => 'Sekolah terdaftar', 'icon' => '▦'],
                ['id' => 'tambah-sekolah', 'label' => 'Daftarkan sekolah', 'icon' => '＋'],
            ]],
            ['title' => 'AKUN PENGELOLA', 'items' => [
                ['id' => 'akun-admin-sekolah', 'label' => 'Admin sekolah', 'icon' => '♙'],
                ['id' => 'buat-akun-admin', 'label' => 'Buat akun admin', 'icon' => '＋'],
            ]],
        ];
    @endphp
    @include('dashboards.partials.portal-sidebar')
    <header class="portal-topbar"><button type="button" class="portal-menu-toggle" aria-label="Buka navigasi" aria-controls="portal-sidebar" aria-expanded="true" data-sidebar-toggle><span></span><span></span><span></span></button><a class="simanis-brand" href="{{ route('foundation.dashboard') }}"><span class="brand-mark"><span></span><span></span><span></span><span></span></span><span>SIMANIS<small>SISTEM INFORMASI AKADEMIK DAN INFORMASI SISWA</small></span></a><div class="portal-user"><span class="portal-avatar">YS</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->job_title }}</small></span><button type="button" class="install-app-button" data-install-app hidden>Pasang aplikasi</button><form action="{{ route('logout') }}" method="POST">@csrf<button class="logout-button" type="submit">Keluar</button></form></div></header>
    <main class="portal-container">
        <div class="portal-heading" id="dashboard-overview"><div><p class="portal-eyebrow">PANEL YAYASAN · SUPER ADMIN</p><h1>Ikhtisar SIMANIS</h1><p>Kelola sekolah yang terhubung dan pantau aktivitas seluruh jaringan pendidikan.</p></div><span class="portal-date">{{ now()->translatedFormat('l, d F Y') }}</span></div>
        @if (session('status')) <div class="portal-alert portal-alert-success">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="portal-alert portal-alert-error">Periksa kembali isian formulir. {{ $errors->first() }}</div> @endif
        <section class="metric-grid">
            <article class="metric-card"><span class="metric-icon">▦</span><small>SEKOLAH TERDAFTAR</small><strong>{{ number_format($schoolCount) }}</strong><span>Institusi pendidikan</span></article>
            <article class="metric-card"><span class="metric-icon metric-icon-violet">♙</span><small>JUMLAH SISWA</small><strong>{{ number_format($studentCount) }}</strong><span>Dari seluruh sekolah</span></article>
            <article class="metric-card"><span class="metric-icon metric-icon-gold">◎</span><small>ADMIN SEKOLAH</small><strong>{{ number_format($staffCount) }}</strong><span>Kepala sekolah, guru, dan TU</span></article>
        </section>
        <section class="portal-columns">
            <article class="portal-card" id="sekolah-terdaftar">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">JARINGAN PENDIDIKAN</p><h2>Sekolah terdaftar</h2></div><span class="soft-count">{{ $schoolCount }} sekolah</span></div>
                <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Nama sekolah</th><th>Lokasi</th><th>Siswa</th><th>Status</th><th>Pengaturan</th></tr></thead><tbody>
                    @forelse ($schools as $school)
                        <tr><td><strong>{{ $school->name }}</strong></td><td>{{ $school->address ?: '—' }}</td><td>{{ $school->students_count }}</td><td><span class="role-pill">{{ $school->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td><td><details class="inline-edit"><summary>Edit</summary><form action="{{ route('foundation.schools.update', $school) }}" method="POST" class="inline-edit-form">@csrf @method('PUT')<label>Nama<input name="name" value="{{ $school->name }}" required></label><label>Alamat<input name="address" value="{{ $school->address }}"></label><label>Status<select name="status"><option value="active" @selected($school->status === 'active')>Aktif</option><option value="inactive" @selected($school->status === 'inactive')>Nonaktif</option></select></label><button class="portal-button portal-button-light" type="submit">Simpan perubahan</button></form></details></td></tr>
                    @empty
                        <tr><td colspan="5" class="table-empty">Belum ada sekolah. Tambahkan sekolah pertama untuk memulai.</td></tr>
                    @endforelse
                </tbody></table></div>
            </article>
            <article class="portal-card portal-form-card" id="tambah-sekolah">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">TAMBAH INSTITUSI</p><h2>Daftarkan sekolah</h2></div></div>
                <form action="{{ route('foundation.schools.store') }}" method="POST" class="portal-form">
                    @csrf
                    <label>Nama sekolah<input name="name" maxlength="160" placeholder="Contoh: SMP Cendekia" required></label>
                    <label>Alamat sekolah<input name="address" maxlength="255" placeholder="Kota atau alamat sekolah"></label>
                    <button class="portal-button" type="submit">Tambah sekolah <span>＋</span></button>
                </form>
            </article>
        </section>
        <section class="portal-columns portal-columns-admin">
            <article class="portal-card" id="akun-admin-sekolah">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">PENGELOLA SEKOLAH</p><h2>Akun kepala sekolah, guru & TU</h2></div></div>
                <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Nama</th><th>Username</th><th>Sekolah</th><th>Peran</th></tr></thead><tbody>
                    @forelse ($administrators as $administrator)
                        <tr><td><strong>{{ $administrator->name }}</strong></td><td>{{ $administrator->username }}</td><td>{{ $administrator->school?->name ?? '—' }}</td><td><span class="role-pill">{{ $administrator->job_title }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="table-empty">Belum ada akun admin sekolah.</td></tr>
                    @endforelse
                </tbody></table></div>
            </article>
            <article class="portal-card portal-form-card" id="buat-akun-admin">
                <div class="portal-section-heading"><div><p class="portal-eyebrow">AKSES ADMINISTRASI</p><h2>Buat akun admin sekolah</h2></div></div>
                <form action="{{ route('foundation.administrators.store') }}" method="POST" class="portal-form">
                    @csrf
                    <label>Nama lengkap<input name="name" maxlength="160" placeholder="Nama administrator" required></label>
                    <div class="portal-form-row"><label>Username<input name="username" maxlength="100" required></label><label>Password<input name="password" type="password" minlength="8" required></label></div>
                    <div class="portal-form-row"><label>Jabatan<select name="job_title" required><option value="">Pilih jabatan</option><option>Kepala sekolah</option><option>Guru</option><option>Tata usaha</option></select></label><label>Sekolah<select name="school_id" required><option value="">Pilih sekolah</option>@foreach ($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></label></div>
                    <button class="portal-button" type="submit" {{ $schoolCount === 0 ? 'disabled' : '' }}>Buat akun administrator <span>＋</span></button>
                </form>
            </article>
        </section>
        <footer class="portal-footer">© {{ now()->year }} SIMANIS <span>Yayasan & Super Admin</span></footer>
    </main>
</body>
</html>
