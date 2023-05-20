<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!file_exists(__DIR__ . '/support/helpers.php')) {
    mkdir(__DIR__ . '/support');
    copy(__DIR__ . '/../vendor/workerman/webman-framework/src/support/helpers.php', __DIR__ . '/support/helpers.php');
}
require_once __DIR__ . '/support/helpers.php';

if (!file_exists(__DIR__ . '/config/plugin/webman-tech/laravel-console')) {
    \WebmanTech\LaravelConsole\Install::install();
}
if (!file_exists(__DIR__ . '/support/bootstrap.php')) {
    copy(__DIR__ . '/../vendor/workerman/webman-framework/src/support/bootstrap.php', __DIR__ . '/support/bootstrap.php');
}

require_once __DIR__ . '/../vendor/workerman/webman-framework/src/support/bootstrap.php';
