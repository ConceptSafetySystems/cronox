<?php

namespace BackSyst\SystemBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetAdminCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('backsyst:admin:reset')
            ->setDescription('Run scheduled backups when needed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sessionMan = $this->getContainer()->get('session_handler');
        $sessionMan->resetAdmin();
        $output->writeln("Admin has been reset.");
    }
}