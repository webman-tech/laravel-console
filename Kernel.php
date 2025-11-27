<?php

namespace WebmanTech\LaravelConsole;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Console\Application as ApplicationContract;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebmanTech\CommonUtils\Container;
use WebmanTech\LaravelConsole\Helper\ArrayHelper;
use WebmanTech\LaravelConsole\Helper\ConfigHelper;
use WebmanTech\LaravelConsole\Helper\ExtComponentGetter;
use WebmanTech\LaravelConsole\Mock\DatabaseMigration\MigrationServiceProvider;
use WebmanTech\LaravelConsole\Mock\LaravelApp;
use function WebmanTech\CommonUtils\app_path;
use function WebmanTech\CommonUtils\base_path;
use function WebmanTech\CommonUtils\config;
use function WebmanTech\CommonUtils\vendor_path;

class Kernel
{
    private array $config = [
        'version' => '1.0.0',
        'name' => 'Webman Artisan',
        'catch_exceptions' => true,
        'commands' => [], // 需要额外补充的 Command 类
        'commands_ignore' => [], // 忽略的 Command 类
        'commands_path' => [
            // path => namespace
        ],
        'commands_scan' => [
            'webman' => true, // 是否扫描 webman/console
            'illuminate_database' => true, // 是否扫描 illuminate/database
        ],
        'namespace' => [
            'command' => 'app\command',
        ]
    ];
    private LaravelApp $app;

    private bool $bootstrapped = false;
    private bool $commandsLoaded = false;

    public function __construct()
    {
        $this->config = ArrayHelper::merge(
            $this->config,
            ConfigHelper::get('artisan', []),
        );

        $container = ExtComponentGetter::get(ContainerContract::class);
        /** @phpstan-ignore-next-line */
        $this->app = new LaravelApp($container, $this->config['version']);
    }

    public function handle(InputInterface $input, ?OutputInterface $output = null): int
    {
        $this->bootstrap();

        /** @phpstan-ignore-next-line */
        return $this->getArtisan()->run($input, $output);
    }

    public function call(string $command, array $parameters = [], $outputBuffer = null): int
    {
        $this->bootstrap();

        return $this->getArtisan()->call($command, $parameters, $outputBuffer);
    }

    public function output(): string
    {
        $this->bootstrap();

        return $this->getArtisan()->output();
    }

    protected function bootstrap()
    {
        if ($this->bootstrapped) {
            return;
        }

        // 注册全部依赖
        $this->app->registerAll();
        // 将自己加入到 container 中，方便后续单独 addCommand 或调其他方法
        $this->app->instance(Kernel::class, $this);
        // 修改 app 配置参数
        $this->app->resolving(ApplicationContract::class, function (Artisan $app) {
            $app->setName($this->config['name']);
            $app->setCatchExceptions($this->config['catch_exceptions']);
        });

        // 安装命令
        if (!$this->commandsLoaded) {
            $this->loadCommands();

            $this->commandsLoaded = true;
        }

        $this->bootstrapped = true;
    }

    protected function loadCommands()
    {
        // 按目录扫描的命令
        $commandPaths = $this->config['commands_path'];
        // 扫描 app/command 目录
        $commandPaths[app_path('command')] = $this->config['namespace']['command'];
        // 扫描 webman/console 目录
        if ($this->config['commands_scan']['webman']) {
            $commandPaths[vendor_path('webman/console/src/Commands')] = 'Webman\Console\Commands';
        }
        // 扫描 illuminate/database 目录
        if ($this->config['commands_scan']['illuminate_database']) {
            $commandPaths[vendor_path('illuminate/database/Console')] = 'Illuminate\Database\Console';
            $this->config['commands_ignore'][] = \Illuminate\Database\Console\Migrations\BaseCommand::class;
            /** @phpstan-ignore-next-line */
            $sp = new MigrationServiceProvider($this->app);
            $sp->register();
        }

        foreach ($commandPaths as $path => $namespace) {
            if (!is_dir($path)) {
                continue;
            }
            $this->installCommands($path, $namespace);
        }

        // 按 class 添加命令
        foreach ($this->config['commands'] as $command) {
            $this->registerCommand($command);
        }

        /**
         * plugin 中的命令
         * @see webman/console 中的 webman
         */
        foreach (config('plugin', []) as $firm => $projects) {
            if (isset($projects['app'])) {
                if ($command_str = self::guessPath(base_path() . "/plugin/$firm", 'command')) {
                    $command_path = base_path() . "/plugin/$firm/$command_str";
                    $this->installCommands($command_path, "plugin\\$firm\\$command_str");
                }
            }
            foreach ($projects as $name => $project) {
                if (!is_array($project)) {
                    continue;
                }
                foreach ($project['command'] ?? [] as $command) {
                    $this->registerCommand($command);
                }
            }
        }
    }

    protected function getArtisan(): ApplicationContract
    {
        return $this->app->get(ApplicationContract::class);
    }

    /**
     * @param $path
     * @param $namespace
     * @return void
     * @see \Webman\Console\Command::installCommands
     */
    public function installCommands($path, $namespace)
    {
        $dir_iterator = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($dir_iterator);
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (strpos($file->getFilename(), '.') === 0) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // abc\def.php
            $relativePath = str_replace(str_replace('/', '\\', $path . '\\'), '', str_replace('/', '\\', $file->getRealPath()));
            // app\command\abc
            $realNamespace = trim($namespace . '\\' . trim(dirname(str_replace('\\', DIRECTORY_SEPARATOR, $relativePath)), '.'), '\\');
            $realNamespace = str_replace('/', '\\', $realNamespace);
            // app\command\doc\def
            $class_name = trim($realNamespace . '\\' . $file->getBasename('.php'), '\\');
            if (!class_exists($class_name) || !is_a($class_name, Command::class, true)) {
                continue;
            }

            $this->registerCommand($class_name);
        }
    }

    /**
     * @param string|Command $command
     * @return void
     */
    public function registerCommand($command)
    {
        if (is_string($command)) {
            if (in_array($command, $this->config['commands_ignore'], true)) {
                return;
            }

            $reflection = new \ReflectionClass($command);
            if ($reflection->isAbstract()) {
                return;
            }
            if ($name = $reflection->getStaticPropertyValue('defaultName', null)) {
                $command = Container::getCurrent()->get($command);
                $command->setName($name);
                if ($description = $reflection->getStaticPropertyValue('defaultDescription', null)) {
                    $command->setDescription($description);
                }
            }
        }

        Artisan::starting(function (Artisan $artisan) use ($command) {
            if ($command instanceof Command) {
                $artisan->add($command);
                return;
            }
            $artisan->resolve($command);
        });
    }

    /**
     * @param $base_path
     * @param $name
     * @param $return_full_path
     * @return false|string
     * @see Util::guessPath()
     */
    private static function guessPath($base_path, $name, $return_full_path = false)
    {
        if (!is_dir($base_path)) {
            return false;
        }
        $names = explode('/', trim(strtolower($name), '/'));
        $realname = [];
        $path = $base_path;
        foreach ($names as $name) {
            $finded = false;
            foreach (scandir($path) ?: [] as $tmp_name) {
                if (strtolower($tmp_name) === $name && is_dir("$path/$tmp_name")) {
                    $path = "$path/$tmp_name";
                    $realname[] = $tmp_name;
                    $finded = true;
                    break;
                }
            }
            if (!$finded) {
                return false;
            }
        }
        $realname = implode(DIRECTORY_SEPARATOR, $realname);
        return $return_full_path ? get_realpath($base_path . DIRECTORY_SEPARATOR . $realname) : $realname;
    }
}
