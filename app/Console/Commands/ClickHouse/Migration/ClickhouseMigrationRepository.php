<?php
namespace App\Console\Commands\ClickHouse\Migration;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;

class ClickhouseMigrationRepository
{
    public function __construct(private readonly string $table, private readonly ConnectionInterface $connection)
    {

    }
    public function createRepository(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        $schema->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('batch');
            $table->dateTime('created_at');
        });
    }

    public function repositoryExists(): bool
    {
        $schema = $this->connection->getSchemaBuilder();
        return $schema->hasTable($this->table);
    }

    /**
     * Delete the migration repository data store.
     *
     * @return void
     */
    public function deleteRepository(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        $schema->drop($this->table);
    }
}
