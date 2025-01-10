# webman-tech/laravel-console

Laravel [illuminate/console](https://packagist.org/packages/illuminate/console) for webman

## 为什么会有这个扩展

官方的 [webman/console](https://packagist.org/packages/webman/console) 很便捷也很实用，而且很贴近 webman 框架，这是事实

以下几个原因是本扩展诞生的原因：

1. webman/console 是基于 symfony/console 的，比较底层，illuminate/console 同样也是，但是在其上层封装了一层，使用起来更加方便
2. 给用惯了 laravel 的人一个熟悉的环境（尤其是在建一个 command 时，laravel 的 command 使用 signature 定义参数更加方便）
3. 现在有很多实用的第三方扩展是基于 laravel，可能迁移其中的组件时能够比较方便，但是其中的 command 无法直接使用，因此本扩展可以让这些扩展的 command 直接使用
4. 支持 illuminate/database 的 Migration 相关功能

## 介绍

所有方法和配置与 laravel 几乎一模一样，因此使用方式完全参考 [Laravel文档](https://laravel.com/docs/artisan) 即可

> 注意：只是用法一致，但不带有任何 laravel 框架自带的命令

## 安装

```bash
composer require webman-tech/laravel-console
```

如果需要支持 webman/console 的命令，直接安装即可

```bash
composer require webman/console
```

## 配置

配置文件：`config/plugin/webman-tech/laravel-console/artisan.php`

### 命令扫描

默认自动扫描 `app\command` 下的命令（同 webman/console 的逻辑）

如果安装了 `webman/console`，会自动扫描其命令（完全兼容）

如果需要自定义命令扫描目录，可以在 `commands_path` 中添加，如：

```php
return [
    'commands_path' => [
         app_path() . '/myCommands' => 'app\myCommands',
    ],
];
```

### 自定义命令

当需要添加来自第三方包，或者没有放在扫描目录中的命令时，可以在 `commands` 中添加，如：

```php
return [
    'commands' => [
        \App\Commands\MyCommand::class,
    ],
];
```

## 使用

1. 新建命令

```php
namespace app\command;
 
use Illuminate\Console\Command;
 
class SendEmails extends Command
{
    protected $signature = 'mail:send {userId}';
 
    protected $description = 'Send a marketing email to a user';
 
    public function handle(Mailer $mailer): void
    {
        $mailer->send(User::find($this->argument('userId')));
    }
}
```

2. 注册命令

由于上面的命令是放在 `app\command` 目录下的，因此不需要注册，
如果是放在其他目录下的，需要在配置中添加

3. 调用

命令行调用

```bash
php artisan mail:send 1
```

业务中调用（比如控制器）

```php
use \WebmanTech\LaravelConsole\Facades\Artisan;

Artisan::call('mail:send', ['userId' => 1]);
```
