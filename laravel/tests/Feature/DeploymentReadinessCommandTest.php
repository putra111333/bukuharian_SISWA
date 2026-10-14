<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentReadinessCommandTest extends TestCase
{
    public function test_deployment_check_reports_non_production_configuration(): void
    {
        $this->artisan('app:deployment-check')
            ->expectsOutputToContain('APP_ENV harus production.')
            ->expectsOutputToContain('Gunakan database server (bukan SQLite) untuk deployment produksi.')
            ->assertExitCode(1);
    }
}
