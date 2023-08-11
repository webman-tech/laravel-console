<?php

namespace WebmanTech\LaravelConsole\Tests\Facades;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use WebmanTech\LaravelConsole\Facades\Artisan;
use WebmanTech\LaravelConsole\Kernel;

/**
 * https://laravel.com/docs/10.x/artisan
 */
class ArtisanTest extends TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf(Kernel::class, Artisan::instance());
    }

    public function testArtisan()
    {
        // version、name 在配置中定义
        $this->assertEquals('Webman Artisan Test 9.9.9', $this->doArtisan('--version'));
        // list
        $listOutput = $this->doArtisan('list');
        // 扫描 webman/console 的命令
        $this->assertStringContainsString('make:controller', $listOutput);
        $this->assertStringContainsString('plugin:create', $listOutput);
        // 扫描 app/command 的命令
        $this->assertStringContainsString('sample:symfony', $listOutput);
        $this->assertStringContainsString('sample:laravel', $listOutput);
        // 扫描 plugin/ 的命令
        $this->assertStringContainsString('sample:tt:symfony', $listOutput);
    }

    public function testCommand()
    {
        $this->assertEquals('sample:symfony result', $this->doArtisan('sample:symfony')); // 支持 symfony command
        $this->assertEquals('sample:laravel result', $this->doArtisan('sample:laravel')); // 支持 laravel command
    }

    public function testArtisanCall()
    {
        $this->assertEquals(0, Artisan::call('sample:laravel'));
        $this->assertEquals($this->doArtisan('sample:laravel'), trim(Artisan::output()));
    }

    protected function doArtisan(string $command): string
    {
        $process = Process::fromShellCommandline('php artisan ' . $command . ' --no-ansi', __DIR__ . '/../');
        $process->run();
        return trim($process->getOutput());
    }
}
