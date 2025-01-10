<?php

namespace WebmanTech\LaravelConsole\Mock\DatabaseMigration;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\DumpCommand as LaravelDumpCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand as LaravelMigrateCommand;
use Illuminate\Database\Migrations\MigrationCreator;

/**
 * @internal
 */
final class MigrationServiceProvider extends \Illuminate\Database\MigrationServiceProvider
{
    public function register()
    {
        parent::register();

        $this->app->singleton(LaravelDumpCommand::class, DumpCommand::class);
    }

    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files'], base_path('stubs'));
        });
    }

    protected function registerMigrateCommand()
    {
        $this->app->singleton(LaravelMigrateCommand::class, function ($app) {
            return new MigrateCommand($app['migrator'], $app[Dispatcher::class]);
        });
    }
}
