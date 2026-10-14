<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckDeploymentReadiness extends Command
{
    protected $signature = 'app:deployment-check';

    protected $description = 'Periksa konfigurasi dasar sebelum SIMANIS dipublikasikan';

    public function handle(): int
    {
        $ready = true;

        $this->check($ready, app()->environment('production'), 'APP_ENV harus production.');
        $this->check($ready, ! config('app.debug'), 'APP_DEBUG harus false.');
        $this->check($ready, filled(config('app.key')), 'APP_KEY harus sudah diatur.');

        $url = (string) config('app.url');
        $this->check(
            $ready,
            parse_url($url, PHP_URL_SCHEME) === 'https' && filled(parse_url($url, PHP_URL_HOST)),
            'APP_URL harus menggunakan domain HTTPS yang valid.',
        );

        $this->check(
            $ready,
            config('database.default') !== 'sqlite',
            'Gunakan database server (bukan SQLite) untuk deployment produksi.',
        );

        $databaseReady = false;
        try {
            DB::connection()->getPdo();
            $databaseReady = true;
        } catch (Throwable) {
            // Keep credentials and connection details out of command output.
        }
        $this->check(
            $ready,
            $databaseReady,
            'Koneksi database gagal; periksa konfigurasi DB_* di server.',
            'Koneksi database dapat diakses.',
        );

        if ($databaseReady) {
            $requiredTables = [
                'users',
                'schools',
                'classrooms',
                'students',
                'assignments',
                'submissions',
            ];
            $missingTables = array_values(array_filter(
                $requiredTables,
                fn (string $table): bool => ! Schema::hasTable($table),
            ));
            $this->check(
                $ready,
                $missingTables === [],
                $missingTables === []
                    ? 'Migrasi database utama belum tersedia.'
                    : 'Tabel database belum lengkap: '.implode(', ', $missingTables).'. Jalankan migrasi.',
                'Migrasi database utama sudah tersedia.',
            );

            if (Schema::hasTable('users')) {
                $demoAccounts = DB::table('users')
                    ->whereIn('username', ['yayasan123', 'admin123', 'user123'])
                    ->pluck('username')
                    ->all();
                $this->check(
                    $ready,
                    $demoAccounts === [],
                    $demoAccounts === []
                        ? 'Akun demo bawaan masih ditemukan.'
                        : 'Hapus atau ganti akun demo sebelum go-live: '.implode(', ', $demoAccounts).'.',
                    'Tidak ditemukan akun demo bawaan.',
                );
            }
        }

        foreach ([
            storage_path(),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            storage_path('app/private'),
            base_path('bootstrap/cache'),
        ] as $directory) {
            $this->check(
                $ready,
                is_dir($directory) && is_writable($directory),
                'Direktori harus tersedia dan dapat ditulis oleh PHP: '.$directory,
            );
        }

        $this->check(
            $ready,
            is_file(public_path('build/manifest.json')),
            'Aset frontend produksi belum tersedia; jalankan npm run build.',
            'Manifest aset frontend produksi tersedia.',
        );

        if ($ready) {
            $this->info('SIMANIS siap untuk pemeriksaan akhir di server.');

            return self::SUCCESS;
        }

        $this->error('Kesiapan deployment belum terpenuhi. Perbaiki pemeriksaan di atas sebelum go-live.');

        return self::FAILURE;
    }

    private function check(bool &$ready, bool $passed, string $message, ?string $successMessage = null): void
    {
        if ($passed) {
            $this->line('<fg=green>[OK]</> '.($successMessage ?? 'Lulus: '.$message));

            return;
        }

        $ready = false;
        $this->line('<fg=red>[GAGAL]</> '.$message);
    }
}
