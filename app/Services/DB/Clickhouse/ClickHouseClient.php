<?php
namespace App\Services\DB\Clickhouse;
interface ClickHouseClient
{
    public function write(string $sql, array $bindings = [], bool $exception = true);
    public function select(string $sql, array $bindings = []);
}
