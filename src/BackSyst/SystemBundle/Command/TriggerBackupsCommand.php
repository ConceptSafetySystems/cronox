<?php

namespace BackSyst\SystemBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TriggerBackupsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('backsyst:backups:trigger')
            ->setDescription('Run scheduled backups when needed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sshMan = $this->getContainer()->get('ssh_connect');
        $backups = $sshMan->getAllBackupsToday();

        foreach ($backups as $backup) {
            $rootdir = $this->getContainer()->get('kernel')->getRootDir();
            $exec = "php $rootdir/console backsyst:backup:run " .$backup->id;
            $exec .= " >/dev/null &";

            exec($exec, $execOutput);
            $output->writeln($execOutput);
        }
    }
}