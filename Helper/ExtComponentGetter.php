<?php

namespace WebmanTech\LaravelConsole\Helper;

use Illuminate\Config\Repository;
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\MigrationServiceProvider;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Support\Composer;
use WebmanTech\LaravelDatabase\Facades\DB;

/**
 * @internal
 */
final class ExtComponentGetter extends BaseExtComponentGetter
{
    protected static function getDefine(): array
    {
        return [
            ContainerContract::class => [
                'singleton' => fn() => LaravelContainer::getInstance(),
            ],
            /** @see EventServiceProvider::register() */
            DispatcherContract::class => [
                'alias' => ['events'],
                'singleton' => fn() => new Dispatcher(LaravelContainer::getInstance()),
            ],
            /** @see FilesystemServiceProvider::registerNativeFilesystem() */
            Filesystem::class => [
                'alias' => ['files'],
                'singleton' => fn() => new Filesystem(),
            ],
            /** @see DatabaseServiceProvider::registerConnectionServices() */
            ConnectionResolverInterface::class => [
                'alias' => ['db'],
                // laravel-database 为可选依赖，未安装时返回 null（migration/schema 使用时才需要）
                'singleton' => fn() => class_exists(DB::class) ? DB::getInstance()->getDatabaseManager() : null,
            ],
            SchemaBuilder::class => [
                'alias' => ['db.schema'],
                // 原先用的 bind，此处暂时先用 singleton，不确定是否会有问题
                'singleton' => function () {
                    $db = self::get(ConnectionResolverInterface::class);
                    $connection = $db?->connection();
                    if ($connection instanceof Connection) {
                        return $connection->getSchemaBuilder();
                    }
                    return null;
                }
            ],
            /** @see MigrationServiceProvider::registerMigrateMakeCommand() */
            /** @see MigrateMakeCommand::__construct */
            Composer::class => [
                'alias' => ['composer'],
                'singleton' => fn() => new Composer(self::get(Filesystem::class)),
            ],
            /** laravel application new 时创建 */
            Repository::class => [
                'alias' => ['config'],
                'singleton' => fn() => new Repository([
                    'database' => [
                        'migrations' => [
                            'table' => 'migrations',
                            'update_date_on_publish' => true,
                        ],
                        ...(array)ConfigHelper::getGlobal('database'),
                    ],
                ]),
            ],
        ];
    }
}
