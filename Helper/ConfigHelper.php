<?php

namespace WebmanTech\LaravelConsole\Helper;

use function WebmanTech\CommonUtils\config;

/**
 * @internal
 */
final class ConfigHelper
{
    /**
     * 获取配置
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return config("plugin.webman-tech.laravel-console.{$key}", $default);
    }

    /**
     * 获取全局配置
     */
    public static function getGlobal(string $key, mixed $default = null): mixed
    {
        return config($key, $default);
    }
}
