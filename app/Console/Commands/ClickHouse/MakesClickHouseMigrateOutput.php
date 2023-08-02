<?php
namespace App\Console\Commands\ClickHouse;
use Throwable;

trait MakesClickHouseMigrateOutput
{
    private function expectedError(Throwable $e) : int
    {
        $this->error('ERROR! Could not Execute Migration');
        $this->warn($e->getMessage());
        $this->warn($e->getFile());
        $this->warn($e->getLine());
        return 1;
    }

    private function unexpectedError(Throwable $e, string $case): int
    {
        $this->error('UNEXPECTED ERROR!');
        $this->warn($e->getMessage());
        $this->warn($e->getFile());
        $this->warn($e->getLine());
        if($this->migrator->getExecutedMigrations()->count()) {
            $this->error("\t" . 'WARNING!!');
            if($case === 'migrate') {
                $this->info('Following migrations have not been rolled backed due to unexpected error, and are now executed:');
            } else {
                $this->info('Following migrations have not been migrated backed due to unexpected error, and are now rolled back:');
            }
            $this->migrator->getExecutedMigrations()->each(function ($migration) {
                $this->warn("\t" . $migration);
            });
        }
        return 2;
    }
}
