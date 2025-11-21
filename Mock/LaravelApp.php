<?php

namespace WebmanTech\LaravelConsole\Mock;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Console\Application as Artisan;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Application as ApplicationContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Facade;
use WebmanTech\LaravelConsole\Helper\ConfigHelper;
use WebmanTech\LaravelConsole\Helper\ExtComponentGetter;

/**
 * @internal
 */
final class LaravelApp implements \Illuminate\Contracts\Container\Container, \ArrayAccess
{
    public function __construct(
        private readonly Container $container,
        private readonly string    $appVersion = '1.0.0'
    )
    {
    }

    private static ?string $databasePath = null;

    public static function getDatabasePath(string $path = ''): string
    {
        if (self::$databasePath === null) {
            self::$databasePath = ConfigHelper::get('artisan.migrate_database_path', 'resource/database');
        }
        return path_combine(base_path(self::$databasePath), $path);
    }

    public function registerAll(): void
    {
        // 以下是 console 下会使用到的组件
        $components = [
            DispatcherContract::class => ['events'],
            Filesystem::class => ['files'],
            ConnectionResolverInterface::class => ['db'],
            SchemaBuilder::class => ['db.schema'],
            Composer::class => ['composer'],
            Repository::class => ['config'],
        ];

        foreach ($components as $id => $alias) {
            if (!$this->container->has($id)) {
                $this->container->singleton($id, fn() => ExtComponentGetter::get($id));
            }
            foreach ($alias as $item) {
                if (!$this->container->has($item)) {
                    $this->container->singleton($item, fn() => ExtComponentGetter::get($id));
                }
            }
        }

        if (!$this->container->has(ApplicationContract::class)) {
            $this->container->singleton(ApplicationContract::class, function () {
                $laravel = new LaravelApp($this->container);
                $app = new Artisan($laravel, $this->container->get(DispatcherContract::class), $this->appVersion);
                $app->setContainerCommandLoader();

                return $app;
            });
        }

        if ($this->container->bound('config')) {
            // 已有 config 配置的，补全 migration 需要的配置
            $config = $this->container->get('config');
            if (!isset($config['database.migrations'])) {
                // 没有该 migrations 会导致无法使用 migrate
                $config['database.migrations'] = [
                    'table' => 'migrations',
                    'update_date_on_publish' => true,
                ];
            }
        }

        /** @phpstan-ignore-next-line */
        Facade::setFacadeApplication($this->container); // 使得迁移脚本中的 Illuminate\Support\Facades\Schema 可用
    }

    public function __call($name, $arguments)
    {
        return match ($name) {
            'runningUnitTests' => false,
            'databasePath' => self::getDatabasePath($arguments[0] ?? ''),
            'environment' => config('app.debug') ? 'local' : 'production',
            'basePath' => base_path($arguments[0] ?? ''),
            'getNamespace' => 'app\\', // 先用 webman 默认的，如果有修改的话暂不支持自动取 composer.json 下的
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
