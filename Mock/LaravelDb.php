<?php

namespace WebmanTech\LaravelConsole\Mock;

use support\Db;

/**
 * @internal
 */
final class LaravelDb extends Db
{
    public static function getInstance()
    {
        return static::$instance;
    }
}
