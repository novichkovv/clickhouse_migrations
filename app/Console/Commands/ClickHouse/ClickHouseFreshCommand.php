<?php

namespace App\Console\Commands\ClickHouse;

use App\Console\Commands\ClickHouse\Migration\ClickhouseMigrationRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ClickHouseFreshCommand extends Command
{
    protected $signature = 'ch:fresh';
    protected $description = 'Refresh Clickhouse Migrations';
    public function handle() : int
    {
        if(config('tracker.reports_connection') !== 'clickhouse') {
            $this->warn('WARNING! Skipped, Requires ClickHouse Connection');
            return 0;
        }
        Artisan::call('ch:rollback', ['--step' => 1000]);
        $this->refreshRepository();
        Artisan::call('ch:migrate');
        return 0;
    }

    private function refreshRepository(): void
    {
        $repo = new ClickhouseMigrationRepository('clickhouse_migrations', DB::connection());
        if($repo->repositoryExists() === true) {
            $repo->deleteRepository();
        }
        $repo->createRepository();
    }
}
