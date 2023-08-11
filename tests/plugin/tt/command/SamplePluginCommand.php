<?php

namespace plugin\tt\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SamplePluginCommand extends Command
{
    protected static $defaultName = 'sample:tt:symfony';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('sample:plugin:symfony result');
        return self::SUCCESS;
    }
}
