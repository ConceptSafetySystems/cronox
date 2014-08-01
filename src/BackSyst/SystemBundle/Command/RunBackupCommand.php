<?php

namespace BackSyst\SystemBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunBackupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('backsyst:backup:run')
            ->setDescription('Run scheduled backups when needed')
            ->addArgument('backupId', InputArgument::REQUIRED, 'You need to enter a backup Id?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sshMan = $this->getContainer()->get('ssh_connect');
        $id = $input->getArgument('backupId');
        $resp = $sshMan->runBackup($id);
        if($resp['success'] === true){
            $output->writeln($resp['backup']);
        }else{
            $output->writeln($resp['error']);
        }
        
    }
}