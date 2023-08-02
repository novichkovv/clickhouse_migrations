<?php

namespace App\Console\Commands\ClickHouse;

use App\Console\Commands\ClickHouse\Migration\ClickhouseMigrationRepository;
use App\Console\Commands\ClickHouse\Migration\ClickHouseMigrator;
use App\Exceptions\ClickHouseMigratorException;
use App\Models\Central\ClickhouseMigration;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ClickHouseMigrateCommand extends Command
{
    use MakesClickHouseMigrateOutput;
    protected $signature = 'ch:migrate';
    protected $description = 'Run Clickhouse Migrations';

    private readonly ClickHouseMigrator $migrator;

    public function __construct()
    {
        $this->migrator = new ClickHouseMigrator;
        parent::__construct();
    }

    public function handle() : int
    {
        if(config('tracker.reports_connection') !== 'clickhouse') {
            $this->warn('WARNING! Skipped, Requires ClickHouse Connection');
            return 0;
        }
        $this->runRepo();
        $migrations = $this->getMigrations();
        if($migrations->isEmpty()) {
            $this->warn('Nothing to migrate');
            return 0;
        }
        return $this->migrate($migrations);
    }

    private function runRepo(): void
    {
        $repo = new ClickhouseMigrationRepository('clickhouse_migrations', DB::connection());
        if($repo->repositoryExists() === false) {
            $repo->createRepository();
        }
    }

    private function getMigrations(): Collection
    {
        $executed_migrations = ClickhouseMigration::all()->pluck('name');
        return $this
            ->getMigrationFilesOrderByDate()
            ->filter(function (string $migration_name) use ($executed_migrations) {
                return $executed_migrations->doesntContain($migration_name);
            });
    }

    private function migrate(Collection $migrations): int
    {
        try {
            $this->migrator->migrate($migrations);
            $this->registerMigrations($migrations);
            return 0;
        } catch (ClickHouseMigratorException $e) {
            $res = $this->expectedError($e);
        } catch (Throwable $e) {
            $res = $this->unexpectedError($e, 'migrate');
        }
        $this->registerMigrations($this->migrator->getExecutedMigrations());
        return $res;
    }

    private function registerMigrations(Collection $migrations): void
    {
        $batch = ClickhouseMigration::getLatestBatch() + 1;
        $migrations->each(function (string $name) use ($batch) {
            ClickhouseMigration::registerMigrated($name, $batch);
            $this->line('Migrated: ' . $name);
        });
    }

    private function getMigrationFilesOrderByDate(): Collection
    {
        $files = Storage::disk('database')->files('clickhouse-migrations');
        $files_collection = collect($files);
        return $files_collection->mapWithKeys(function ($migration_file) {
            $name = basename($migration_file, '.php');
            $name_arr = explode('_', $name);
            $number = (int) implode('', array_slice($name_arr, 0, 4));
            return [$number => $name];
        })
            ->sortKeys()
            ->values();
    }
}
