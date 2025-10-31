# webman-tech/laravel-console

> Split from [webman-tech/laravel-monorepo](https://github.com/webman-tech/laravel-monorepo)

适用于 webman 的 Laravel 控制台组件，基于 illuminate/console 实现。

## 安装

```bash
composer require webman-tech/laravel-console
```

如果需要同时使用 webman/console 的命令，还需要安装：

```bash
composer require webman/console
```

## 简介

该组件将 Laravel 强大的 Artisan 命令行功能引入 webman 框架中，使开发者能够使用 Laravel 的命令行语法创建和管理命令。

所有方法和配置与 Laravel 几乎一致，因此使用方式可完全参考 [Laravel Artisan 文档](https://laravel.com/docs/artisan)。

## 特殊使用说明

### 1. 为什么会有这个扩展

相比于官方的 [webman/console](https://packagist.org/packages/webman/console)：

1. 基于 illuminate/console，使用更方便
2. 给用惯了 Laravel 的人一个熟悉的环境
3. 支持第三方 Laravel 包的命令直接使用
4. 支持 illuminate/database 的 Migration 相关功能

### 2. Facades 使用方式

使用 `WebmanTech\LaravelConsole\Facades\Artisan` 替代 Laravel 的 Artisan Facade：

```php
use WebmanTech\LaravelConsole\Facades\Artisan;

// 调用命令
Artisan::call('mail:send', [
    'userId' => 123
]);
```

### 3. 命令扫描配置

默认自动扫描 `app\command` 下的命令。

配置文件：`config/plugin/webman-tech/laravel-console/artisan.php`

```php
return [
    // 自定义命令扫描路径
    'commands_path' => [
        app_path() . '/MyCommands' => 'app\MyCommands',
    ],
    
    // 直接注册命令类
    'commands' => [
        \App\Commands\MyCommand::class,
    ],
];
```

### 4. 创建命令

```php
namespace app\command;
 
use Illuminate\Console\Command;
 
class SendEmails extends Command
{
    protected $signature = 'mail:send {userId}';
 
    protected $description = 'Send a marketing email to a user';
 
    public function handle(): void
    {
        // 处理逻辑
    }
}
```

### 5. 调用命令

命令行调用：

```bash
php artisan mail:send 1
```

业务中调用：

```php
use WebmanTech\LaravelConsole\Facades\Artisan;

Artisan::call('mail:send', ['userId' => 1]);
```

## 注意事项

### 当使用 db:seed 报错 "Target class [Database\Seeders\Xxxx] does not exist" 时

解决办法：主动在 `composer.json` 中添加 psr-4
的配置，详见 [issue-9](https://github.com/webman-tech/laravel-console/issues/9) 
