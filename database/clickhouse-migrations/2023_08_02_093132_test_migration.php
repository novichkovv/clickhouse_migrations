<?php

use App\Console\Commands\ClickHouse\Migration\AbstractClickHouseMigration;

return new class extends AbstractClickHouseMigration
{
    public function up(): void
    {
        $this->client->write(<<<SQL
        CREATE TABLE visits
        (
            vid String,
            workspace_id UInt64,
            version UInt8,
            visit_date DateTime,
            created_at DateTime
        )
        ENGINE = ReplacingMergeTree(version)
        PRIMARY KEY (vid, workspace_id)
        SQL);
    }

    public function down(): void
    {
        $this->client->write(<<<SQL
        DROP TABLE visits
        SQL);
    }
};
