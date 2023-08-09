<?php

namespace app\command;

use Illuminate\Console\Command;

class SampleLaravelCommand extends Command
{
    protected $signature = 'sample:laravel';

    public function handle(): void
    {
        $this->info('sample:laravel result');
    }
}
