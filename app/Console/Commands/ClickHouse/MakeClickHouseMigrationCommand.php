<?php

namespace App\Console\Commands\ClickHouse;

use Illuminate\Console\Command;

class MakeClickHouseMigrationCommand extends Command
{
    protected $signature = 'ch:make-migration {name}';

    protected $description = 'Create a migration file for clickhouse';

    public function handle(): int
    {
        $name = $this->argument('name');
        $dir = database_path('clickhouse-migrations');
        $name = gmdate('Y_m_d_his') . '_' . $name . '.php';
        $file_name = $dir . '/' .  $name;
        $stub = file_get_contents(base_path('stubs/clickhouse-migration.stub'));
        file_put_contents($file_name, $stub);
        return 0;
    }
}
