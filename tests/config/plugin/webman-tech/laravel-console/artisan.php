<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;

return [
    /**
     * @see \WebmanTech\LaravelConsole\Kernel::$config
     */
    'version' => '9.9.9',
    'name' => 'Webman Artisan Test',
    'container' => function (): ContainerContract {
        $container = Container::getInstance();
        // add some deps
        return $container;
    },
    'commands' => [
        // commandName
    ],
    'commands_path' => [
        dirname(base_path()) . '/vendor/webman/console/src/Commands' => 'Webman\Console\Commands', // tests 下需要手动设置
    ],
];
