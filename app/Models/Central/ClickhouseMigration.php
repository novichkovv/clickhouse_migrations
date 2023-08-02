<?php

namespace App\Models\Central;

use Eloquent;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Eloquent
 * @class ClickHouseMigration
 * @package App\Models
 * @property int id
 * @property string name
 * @property int batch
 */

class ClickhouseMigration extends Model
{
    protected $guarded = ['id'];

    public static function getLatestBatch(): int
    {

        return self::orderBy('batch', 'desc')->first('batch')->batch ?? 0;
    }

    public static function registerMigrated(string $name, int $batch)
    {
        self::create([
            'name' => $name,
            'batch' => $batch
        ]);
    }

    public static function registerRolledBack(string $name)
    {
        self::where('name', $name)->delete();
    }

    public function getUpdatedAtColumn() {
        return null;
    }
}
