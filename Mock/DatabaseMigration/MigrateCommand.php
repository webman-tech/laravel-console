<?php

namespace WebmanTech\LaravelConsole\Mock\DatabaseMigration;

use WebmanTech\LaravelConsole\Mock\LaravelApp;

if (!function_exists('WebmanTech\LaravelConsole\Mock\DatabaseMigration\database_path')) {
    function database_path(string $path = '')
    {
        return LaravelApp::getDatabasePath($path);
    }
}

/**
 * 以下代码无变更，仅仅为了解决 database_path 问题
 * @internal
 */
final class MigrateCommand extends \Illuminate\Database\Console\Migrations\MigrateCommand
{
    protected function schemaPath($connection)
    {
        if ($this->option('schema-path')) {
            return $this->option('schema-path');
        }

        if (file_exists($path = database_path('schema/' . $connection->getName() . '-schema.dump'))) {
            return $path;
        }

        return database_path('schema/' . $connection->getName() . '-schema.sql');
    }
}
