<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SampleSymfonyCommand extends Command
{
    protected static $defaultName = 'sample:laravel';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('sample:laravel result');
        return self::SUCCESS;
    }
}
