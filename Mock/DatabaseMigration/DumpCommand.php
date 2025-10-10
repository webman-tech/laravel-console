<?php

namespace WebmanTech\LaravelConsole\Mock\DatabaseMigration;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\MigrationsPruned;
use Illuminate\Database\Events\SchemaDumped;
use Illuminate\Filesystem\Filesystem;
use WebmanTech\LaravelConsole\Mock\LaravelApp;

if (!function_exists('WebmanTech\LaravelConsole\Mock\DatabaseMigration\database_path')) {
    function database_path(string $path = '')
    {
        return path_combine(base_path(LaravelApp::DATABASE_PATH), $path);
    }
}

/**
 * 以下代码无变更，仅仅为了解决 database_path 问题
 * @internal
 */
final class DumpCommand extends \Illuminate\Database\Console\DumpCommand
{
    public function handle(ConnectionResolverInterface $connections, Dispatcher $dispatcher)
    {
        /** @var Connection $connection */
        $connection = $connections->connection($database = $this->input->getOption('database'));

        $this->schemaState($connection)->dump(
            $connection, $path = $this->path($connection)
        );

        $dispatcher->dispatch(new SchemaDumped($connection, $path));

        $info = 'Database schema dumped';

        if ($this->option('prune')) {
            (new Filesystem)->deleteDirectory(
                $path = database_path('migrations'), $preserve = false
            );

            $info .= ' and pruned';

            $dispatcher->dispatch(new MigrationsPruned($connection, $path));
        }

        $this->components->info($info . ' successfully.');
    }

    protected function path(Connection $connection)
    {
        return tap($this->option('path') ?: database_path('schema/' . $connection->getName() . '-schema.sql'), function ($path) {
            (new Filesystem)->ensureDirectoryExists(dirname($path));
        });
    }
}
