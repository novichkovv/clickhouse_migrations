<?php
namespace App\Console\Commands\ClickHouse\Migration;
use App\Services\DB\Clickhouse\ClickHouseClient;
use Illuminate\Contracts\Container\BindingResolutionException;

abstract class AbstractClickHouseMigration
{
    /**
     * @var ClickHouseClient|mixed
     */
    protected ClickHouseClient $client;

    /**
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        $this->client = app()->make(ClickHouseClient::class);
    }

    abstract function up() : void;

    abstract function down() : void;
}
