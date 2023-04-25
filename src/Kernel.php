<?php

namespace WebmanTech\LaravelConsole;

use Illuminate\Console\Application;
use Illuminate\Contracts\Console\Application as ApplicationContract;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel
{
    private array $config = [
        'container' => null,
        'version' => '1.0.0',
        'name' => 'Webman Artisan',
        'commands' => [
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
            config('plugin.webman-tech.laravel-console.artisan', [
                'container' => fn() => null,
            ])
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
                $app = new Application($this->container, $this->container->get(DispatcherContract::class), $this->config['version']);
                $app->setName($this->config['name']);
                $app->setCatchExceptions(true);
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
        $commands = array_merge([
            base_path() . '/vendor/webman/console/src/Commands' => 'Webman\Console\Commands',
            app_path() . '/command' => 'app\command',
        ], $this->config['commands']);

        foreach ($commands as $path => $namespace) {
            if (!is_dir($path)) {
                continue;
            }
            $this->installCommands($path, $namespace);
        }

        // plugin 中的命令
        foreach (config('plugin', []) as $firm => $projects) {
            if (isset($projects['app'])) {
                if ($command_str = (base_path() . "/plugin/$firm/command")) {
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
        Application::starting(function (Application $artisan) use ($command) {
            if ($command instanceof Command) {
                $artisan->add($command);
                return;
            }
            $artisan->resolve($command);
        });
    }
}
