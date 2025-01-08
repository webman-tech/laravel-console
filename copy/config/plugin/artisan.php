<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;

return [
    /**
     * @see \WebmanTech\LaravelConsole\Kernel::$config
     */
    'container' => function (): ContainerContract {
        $container = Container::getInstance();
        // add some deps
        return $container;
    },
    'commands' => [
        // commandName
    ],
];
