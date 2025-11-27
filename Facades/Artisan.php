<?php

namespace WebmanTech\LaravelConsole\Facades;

use WebmanTech\CommonUtils\Container;
use WebmanTech\LaravelConsole\Kernel;

/**
 * @method static int handle(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface|null $output = null)
 * @method static void terminate(\Symfony\Component\Console\Input\InputInterface $input, int $status)
 * @method static void whenCommandLifecycleIsLongerThan(\DateTimeInterface|\Carbon\CarbonInterval|float|int $threshold, callable $handler)
 * @method static \Illuminate\Support\Carbon|null commandStartedAt()
 * @method static \Illuminate\Console\Scheduling\Schedule resolveConsoleSchedule()
 * @method static void registerCommand(\Symfony\Component\Console\Command\Command $command)
 * @method static int call(\Symfony\Component\Console\Command\Command|string $command, array $parameters = [], \Symfony\Component\Console\Output\OutputInterface|null $outputBuffer = null)
 * @method static array all()
 * @method static string output()
 * @method static void bootstrap()
 * @method static void bootstrapWithoutBootingProviders()
 * @method static void setArtisan(\Illuminate\Console\Application|null $artisan)
 * @method static \WebmanTech\LaravelConsole\Kernel addCommands(array $commands)
 * @method static \WebmanTech\LaravelConsole\Kernel addCommandPaths(array $paths)
 * @method static \WebmanTech\LaravelConsole\Kernel addCommandRoutePaths(array $paths)
 *
 * @see \WebmanTech\LaravelConsole\Kernel
 */
class Artisan
{
    /**
     * @var null|Kernel
     */
    protected static ?Kernel $_instance = null;

    public static function instance(): Kernel
    {
        if (!static::$_instance) {
            static::$_instance = Container::getCurrent()->make(Kernel::class, []);
        }
        return static::$_instance;
    }

    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }
}
