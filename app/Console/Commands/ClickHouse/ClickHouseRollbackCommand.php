<?php

namespace App\Console\Commands\ClickHouse;

use App\Console\Commands\ClickHouse\Migration\ClickhouseMigrationRepository;
use App\Console\Commands\ClickHouse\Migration\ClickHouseMigrator;
use App\Exceptions\ClickHouseMigratorException;
use App\Models\Central\ClickhouseMigration;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;
class ClickHouseRollbackCommand extends Command
{

    use MakesClickHouseMigrateOutput;
    protected $signature = 'ch:rollback {--step=} {--batch=}';
    protected $description = 'Rollback Clickhouse Migrations';
    private readonly ClickHouseMigrator $migrator;

    public function __construct()
    {
        $this->migrator = new ClickHouseMigrator();
        parent::__construct();
    }

    /**
     * @throws ClickHouseMigratorException
     */
    public function handle() : int
    {
        if(config('tracker.reports_connection') !== 'clickhouse') {
            $this->warn('WARNING! Skipped, Requires ClickHouse Connection');
            return 0;
        }
        if($this->repositoryExists() === false) {
            $this->warn('Nothing to rollback - migrations table does not exist');
            return 0;
        }
        $migrations = $this->getMigrations();
        if($migrations->isEmpty()) {
            $this->warn('Nothing to rollback');
            return 0;
        }
        return $this->migrate($migrations);
    }

    private function repositoryExists(): bool
    {
        $repo = new ClickhouseMigrationRepository('clickhouse_migrations', DB::connection());
        return $repo->repositoryExists();
    }

    private function migrate(Collection $migrations) : int
    {
        try {
            $this->migrator->rollback($migrations);
            $this->registerMigrations($migrations);
            return 0;
        } catch (ClickHouseMigratorException $e) {
            $res = $this->expectedError($e);
        } catch (Throwable $e) {
            $res = $this->unexpectedError($e, 'rollback');
        }
        $this->registerMigrations($this->migrator->getExecutedMigrations());
        return $res;
    }

    private function registerMigrations(Collection $migrations): void
    {
        $migrations->each(function (string $name) {
            ClickhouseMigration::registerRolledBack($name);
            $this->line('Rolled Back: ' . $name);
        });
    }


    /**
     * @throws ClickHouseMigratorException
     */
    private function getMigrations(): Collection
    {
        $builder = $this->getBuilder();
        return $builder
            ->get()
            ->map(function (ClickhouseMigration $migration) {
                return $migration->name;
            });
    }

    /**
     * @throws ClickHouseMigratorException
     */
    private function getBuilder(): Builder
    {
        $step = $this->options()['step'];
        $batch = $this->options()['batch'];
        $builder = ClickhouseMigration::query();
        if($batch) {
            $builder->where('batch', $batch);
        }
        if($step) {
            $builder->limit($step);
        }
        if(!$step && !$batch) {
            $builder->where('batch', $this->getBatch());
        }
        $builder->orderBy('id', 'desc');
        return $builder;
    }

    /**
     * @throws ClickHouseMigratorException
     */
    private function getBatch(): int
    {
        return ClickhouseMigration::getLatestBatch()
            ?? throw new ClickHouseMigratorException('Migrations are empty');
    }
}
