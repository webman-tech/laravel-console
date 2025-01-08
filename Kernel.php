<?php

namespace WebmanTech\LaravelConsole;

use Illuminate\Console\Application;
use Illuminate\Console\Concerns\ConfiguresPrompts;
use Illuminate\Contracts\Console\Application as ApplicationContract;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use InvalidArgumentException;
use support\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebmanTech\LaravelConsole\Helper\ConfigHelper;
use WebmanTech\LaravelConsole\Wrapper\LaravelContainerWrapper;

class Kernel
{
    private array $config = [
        'container' => null,
        'version' => '1.0.0',
        'name' => 'Webman Artisan',
        'commands' => [],
        'commands_path' => [
            // path => namespace
        ],
    ];
    private ContainerContract $container;

    private bool $bootstrapped = false;
    private bool $commandsLoaded = false;

    public function __construct()
    {
        $this->config = array_merge(
            $this->config,
            ConfigHelper::get('artisan', [
                'container' => fn() => null,
            ]),
        );

        $container = $this->config['container']();
        if (!$container instanceof ContainerContract) {
            throw new InvalidArgumentException('container must be an instance of ' . ContainerContract::class);
        }
        $this->container = $container;
    }

    public function handle(InputInterface $input, ?OutputInterface $output = null): int
    {
        $this->bootstrap();

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

        // 添加必要依赖
        if (!$this->container->has(DispatcherContract::class)) {
            $this->container->singleton(DispatcherContract::class, function () {
                return new Dispatcher($this->container);
            });
        }
        if (!$this->container->has(ApplicationContract::class)) {
            $this->container->singleton(ApplicationContract::class, function () {
                $laravel = $this->container;
                if (trait_exists(ConfiguresPrompts::class)) {
                    // 为了解决 ConfiguresPrompts 中通过 $this->laravel->runningUnitTests() 的问题
                    // https://github.com/webman-tech/laravel-console/issues/2
                    $laravel = new LaravelContainerWrapper($laravel);
                }
                $app = new Application($laravel, $this->container->get(DispatcherContract::class), $this->config['version']);
                $app->setName($this->config['name']);
                $app->setCatchExceptions(true);
                // fix for illuminate/console >= 9
                if (method_exists($app, 'setContainerCommandLoader')) {
                    $app->setContainerCommandLoader();
                }
                return $app;
            });
        }
        // 将自己加入到 container 中，方便后续单独 addCommand 或调其他方法
        if (!$this->container->has(Kernel::class)) {
            $this->container->singleton(Kernel::class, function () {
                return $this;
            });
        }
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
        $commandPaths = array_merge([
            base_path('vendor/webman/console/src/Commands') => 'Webman\Console\Commands',
            app_path('command') => 'app\command',
        ], $this->config['commands_path']);

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
        return $this->container->get(ApplicationContract::class);
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
            $reflection = new \ReflectionClass($command);
            if (!$reflection->isAbstract()) {
                if ($name = $reflection->getStaticPropertyValue('defaultName', null)) {
                    $command = Container::get($command);
                    $command->setName($name);
                    if ($description = $reflection->getStaticPropertyValue('defaultDescription', null)) {
                        $command->setDescription($description);
                    }
                }
            }
        }

        Application::starting(function (Application $artisan) use ($command) {
            if ($command instanceof Command) {
                $artisan->add($command);
                return;
            }
            $artisan->resolve($command);
        });
    }

    /**
     * @see Util::guessPath()
     * @param $base_path
     * @param $name
     * @param $return_full_path
     * @return false|string
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
