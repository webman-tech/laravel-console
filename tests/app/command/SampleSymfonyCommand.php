<?php

namespace app\command;

use Illuminate\Console\Command;

class SampleSymfonyCommand extends Command
{
    protected $signature = 'sample:symfony';

    public function handle(): void
    {
        $this->info('sample:symfony result');
    }
}
