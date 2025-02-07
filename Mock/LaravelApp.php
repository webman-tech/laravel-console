<?php

namespace WebmanTech\LaravelConsole\Mock;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Console\Application as Artisan;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Console\Application as ApplicationContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Facade;
use WebmanTech\LaravelConsole\Helper\ConfigHelper;
use WebmanTech\LaravelConsole\Helper\ExtComponentGetter;
use WebmanTech\LaravelFilesystem\Facades\File;

/**
 * @internal
 */
final class LaravelApp implements \Illuminate\Contracts\Container\Container, \ArrayAccess
{
    public const DATABASE_PATH = 'resource/database';

    public function __construct(
        private readonly Container $container,
        private readonly string    $appVersion = '1.0.0'
    )
    {
    }

    public function registerAll(): void
    {
        $components = [
            'events' => [
                'default' => fn() => new Dispatcher($this->container),
            ],
            ApplicationContract::class => [
                'default' => function () {
                    $laravel = new LaravelApp($this->container);
                    $app = new Artisan($laravel, $this->container->get(DispatcherContract::class), $this->appVersion);
                    $app->setContainerCommandLoader();

                    return $app;
                },
            ],
            'files' => [
                File::class => fn() => File::instance(),
                'default' => fn() => new Filesystem(),
            ],
            'db' => [
                'default' => function () {
                    return LaravelDb::getInstance()->getDatabaseManager();
                }
            ],
            'db.schema' => [
                'default' => fn() => $this->container->get('db')->getSchemaBuilder(),
            ],
            'composer' => [
                'default' => fn() => new Composer($this->container->get('files')),
            ],
            'config' => [
                'default' => fn() => new Repository([
                    'database' => [
                        'migrations' => [
                            'table' => 'migrations',
                            'update_date_on_publish' => true,
                        ],
                        ...ConfigHelper::getGlobal('database'),
                    ],
                ]),
            ],
        ];
        $aliases = [
            'db' => [ConnectionResolverInterface::class],
            'events' => [DispatcherContract::class],
            'files' => [Filesystem::class],
            'config' => [ConfigContract::class],
        ];

        foreach ($components as $component => $config) {
            if (!$this->container->has($component)) {
                $this->container->singleton($component, fn() => ExtComponentGetter::getNoCheck([
                    $component,
                    ...$aliases[$component] ?? [],
                ], $config));
            }
        }
        foreach ($aliases as $alias => $abstracts) {
            foreach ($abstracts as $abstract) {
                if (!$this->container->has($abstract)) {
                    $this->container->alias($alias, $abstract);
                }
            }
        }

        $this->container->resolving('config', function ($config) {
            if (!isset($config['database.migrations'])) {
                // 没有该 migrations 会导致无法使用 migrate
                $config['database.migrations'] = [
                    'table' => 'migrations',
                    'update_date_on_publish' => true,
                ];
            }
            return $config;
        });

        Facade::setFacadeApplication($this->container); // 使得迁移脚本中的 Illuminate\Support\Facades\Schema 可用
    }

    public function __call($name, $arguments)
    {
        return match ($name) {
            'runningUnitTests' => false,
            'databasePath' => path_combine(base_path(self::DATABASE_PATH), $arguments[0] ?? ''),
            'environment' => config('app.debug') ? 'local' : 'production',
            default => $this->container->{$name}(...$arguments),
        };
    }

    /**
     * @inheritDoc
     */
    public function bound($abstract)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function alias($abstract, $alias)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function tag($abstracts, $tags)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function tagged($tag)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function bindMethod($method, $callback)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function bindIf($abstract, $concrete = null, $shared = false)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function singleton($abstract, $concrete = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function singletonIf($abstract, $concrete = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function scoped($abstract, $concrete = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function scopedIf($abstract, $concrete = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function extend($abstract, Closure $closure)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function instance($abstract, $instance)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function addContextualBinding($concrete, $abstract, $implementation)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function when($concrete)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function factory($abstract)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function make($abstract, array $parameters = [])
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function resolved($abstract)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function beforeResolving($abstract, ?Closure $callback = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function resolving($abstract, ?Closure $callback = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function afterResolving($abstract, ?Closure $callback = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->__call(__FUNCTION__, func_get_args());
    }
}
