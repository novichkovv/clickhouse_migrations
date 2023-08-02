<?php
namespace App\Console\Commands\ClickHouse\Migration;
use App\Exceptions\ClickHouseMigratorException;
use Illuminate\Support\Collection;
use Throwable;

class ClickHouseMigrator
{
    private Collection $executed_migrations;
    /**
     * @param Collection<string> $migrations
     * @return void
     * @throws ClickHouseMigratorException
     */
    public function migrate(Collection $migrations): void
    {
        $this->runMigration($migrations, 'up');
    }

    /**
     * @param Collection<string> $migrations
     * @return void
     * @throws ClickHouseMigratorException
     */

    public function rollback(Collection $migrations): void
    {
        $this->runMigration($migrations, 'down');
    }

    /**
     * @param Collection $migrations
     * @param string $direction
     * @return void
     * @throws ClickHouseMigratorException
     */

    private function runMigration(Collection $migrations, string $direction): void
    {
        $this->executed_migrations = collect();
        $migrations->each(function (string $name) use ($direction) {
            try {
                $migration = $this->getMigration($name);
                $migration->$direction();
                $this->executed_migrations->put($name, $migration);
            } catch (Throwable $e) {
                //always throws ClickHouseMigratorException after rolling back
                $this->rollbackCollection($e, $this->getOpposite($direction));
            }
        });
    }

    private function getOpposite(string $direction): string
    {
        return $direction === 'up' ? 'down' : 'up';
    }


    /**
     * @throws ClickHouseMigratorException
     */
    private function rollbackCollection(Throwable $e, string $direction): void
    {

        $this->executed_migrations->reverse();
        $this->executed_migrations = $this->executed_migrations //remove rolled back from executed
            ->reject(function (AbstractClickHouseMigration $migration) use ($direction) {
                $migration->$direction();
                return true;
            });
        throw new ClickHouseMigratorException($e->getMessage());
    }

    private function getMigration(string $file_name) : AbstractClickHouseMigration
    {
        $full_file_name = database_path('clickhouse-migrations') . '/' . $file_name . '.php';
        return require $full_file_name;
    }

    public function getExecutedMigrations(): Collection
    {
        return $this->executed_migrations->keys();
    }
}
