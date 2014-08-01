<?php

namespace BackSyst\SystemBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunWatchCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('backsyst:backups:watch')
            ->setDescription('Watch running processes on remote machines.')
            ->addArgument('reportId', InputArgument::REQUIRED, "You need to enter report Id, or 'All' if you want to watch them automatically");
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sshMan = $this->getContainer()->get('ssh_connect');
        $id = $input->getArgument('reportId');

        if(strtolower($id) == 'all'){
            $response = $ssMan->watchProcesses();
        }else{
            $response = $sshMan->watchProcess($id);
            if($response['success'] === true){
                $backupName = $response['backupName'];
                $output->writeln("Watching backup $backupName");
            }else{
                $error = $response['error'];
                $output->writeln("An error occured: $error");
            }
        }
    }
}