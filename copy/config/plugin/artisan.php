<?php

return [
    /**
     * @see \WebmanTech\LaravelConsole\Kernel::$config
     */
    // 自定义 Command
    'commands' => [
        // commandName
    ],
    'commands_scan' => [
        'webman' => true, // 是否扫描 webman/console
        'illuminate_database' => true, // 是否扫描 illuminate/database
    ],
    'migrate_database_path' => 'resource/database', // 迁移数据库目录路径
];
