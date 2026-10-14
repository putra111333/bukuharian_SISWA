<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#42313A">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/simanis.svg') }}" type="image/svg+xml">
    <title>Masuk — SIMANIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-body login-body">
    <main class="login-layout">
        <section class="login-story">
            <a class="simanis-brand" href="{{ route('login') }}"><span class="brand-mark"><span></span><span></span><span></span><span></span></span><span>SIMANIS<small>SISTEM INFORMASI AKADEMIK DAN INFORMASI SISWA</small></span></a>
            <div class="login-story-copy">
                <span class="portal-eyebrow">SATU RUANG UNTUK BERTUMBUH</span>
                <h1>Langkah kecil hari ini,<br>masa depan gemilang.</h1>
                <p>Pantau perjalanan pendidikan, terhubung dengan sekolah, dan rayakan setiap pencapaian bersama SIMANIS.</p>
                <div class="login-stats"><span><strong>Belajar</strong><small>Lebih terarah</small></span><span><strong>Bertumbuh</strong><small>Bersama sekolah</small></span><span><strong>Terhubung</strong><small>Setiap harinya</small></span></div>
            </div>
            <span class="login-decoration login-decoration-one">✳</span><span class="login-decoration login-decoration-two">✦</span>
            <div class="login-story-footer">Satu ekosistem pendidikan, untuk setiap langkah.</div>
        </section>
        <section class="login-panel">
            <div class="login-form-wrap">
                <span class="login-mobile-brand">SIMANIS</span>
                <p class="portal-eyebrow">SELAMAT DATANG KEMBALI</p>
                <h2>Masuk ke akunmu</h2>
                <p class="login-help">Gunakan username dan password yang diberikan sekolah atau yayasan.</p>
                @if (session('status'))
                    <div class="portal-alert portal-alert-success">{{ session('status') }}</div>
                @endif
                <form action="{{ route('login.store') }}" method="POST" class="login-form">
                    @csrf
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" placeholder="Masukkan username" required autofocus>
                    @error('username') <span class="field-error">{{ $message }}</span> @enderror
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                    <label class="remember-field"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
                    <button type="submit" class="portal-button login-submit">Masuk ke SIMANIS <span>→</span></button>
                </form>
                @if (app()->environment('local', 'testing'))
                    <div class="demo-access">
                        <strong>Akun demo</strong>
                        <span>Gunakan password yang sama dengan username.</span>
                        <div class="demo-buttons">
                            <button type="button" data-demo-login="yayasan123">Yayasan</button>
                            <button type="button" data-demo-login="admin123">Admin sekolah</button>
                            <button type="button" data-demo-login="user123">Orang tua</button>
                        </div>
                    </div>
                @endif
                <p class="login-privacy">Akun siswa dan orang tua bersifat pribadi dan hanya dapat melihat data siswa yang ditautkan.</p>
                <button type="button" class="install-app-button login-install-button" data-install-app hidden>Pasang SIMANIS di desktop</button>
            </div>
            <footer class="login-footer">© {{ now()->year }} SIMANIS <span>·</span> Sistem Informasi Akademik Dan Informasi Siswa</footer>
        </section>
    </main>
</body>
</html>
