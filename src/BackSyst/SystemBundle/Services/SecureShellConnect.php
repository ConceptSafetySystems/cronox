<?php

/**
 *  Author: Nicolas Dijoux
 *  Year: 2014
 *  Organisation: Locatrix Communication
 *  Copyright: Locatrix Communication - All rights reserved
 *
 */

namespace BackSyst\SystemBundle\Services;
use BackSyst\SystemBundle\Entity\SSHKnownHosts;
use BackSyst\SystemBundle\Entity\Backup;
use BackSyst\SystemBundle\Entity\Report;
use BackSyst\SystemBundle\Entity\WatchProcess;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use Sinner\Phpseclib\Net\Net_SCP as NetScp;
use Sinner\Phpseclib\Net\Net_SSH2 as NetSsh2;
use Sinner\Phpseclib\Crypt\Crypt_RSA as CryptRsa;

class SecureShellConnect
{

    protected $adminRepo = null;
    protected $knownHostsRepo = null;

	/**
     *  The constructor
     *  Remark: Arguments are set in service's declaration in HRPortal\SystemBundle\Resources\config\services.yml
     *  @param (EntityManager) $em, is a Symfony2 Entity Manager
     *  @param $session, is the global session array
     */
    public function __construct(EntityManager $em, $session, $container)
    {
        $this->em = $em;
        $this->adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
        $this->knownHostsRepo = $em->getRepository('BackSystSystemBundle:SSHKnownHosts');
        $this->session = $session;
        $this->container = $container;
    }

    public function testConnection($machine)
    {
        $user = $machine->user;
        $host = $machine->host;
        $admin = $this->adminRepo->find(1);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $machine->host);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        
        // Checking if host is reachable
        $isReachable = $this->isHostReachable($machine);
        if($isReachable === false){
            $this->initReport($machine, $script->script, null, false, "CRITICAL", "The host could not be reached");
            return array('success'=>false, 'error'=>'The host could not be reached');
        }

        return $this->attemptSshLogin($machine);
    }

    public function getIpAddress($hostUrl)
    {
        $hostIp = gethostbynamel($hostUrl);
        $hostIp = $hostIp[0];
        return $hostIp;
    }

    public function runBackup($backupId)
    {
        $backupRepo = $this->em->getRepository('BackSystSystemBundle:Backup');
        $b = $backupRepo->find($backupId);

        if($b == null){
            return array('success'=>false, 'error'=>"Backup Not Found");
        }else{
            $this->runScript($b, $b->machine, $b->commandLine);
            $output = "Running " .$b->title;
            return array('success'=>true, 'backup'=>$output);
        }
    }

    public function runScript($backup, $machine, $command)
    {   
        $priorCheckPassed = true;
        $priorCheckError = $script = $user = $host = "";
    
        if($backup != null){
            $script = $backup->script;
            if($machine != null){
                $user = $machine->user;
                $host = $machine->host;
                if($machine->isTestSuccess === false){
                    $priorCheckPassed = false;
                    $priorCheckError .= 'The remote machine must be tested prior to be used.';
                    $newReport = $this->initReport($machine, $script->script, $backup, null, "CRITICAL", $priorCheckError);
                    
                }
            }else{
                $priorCheckPassed = false;
                $newReport = $this->initReport($machine,$script->script, $backup, null, "CRITICAL", "");
                $priorCheckError .= 'Details about the remote machine could not be found in database. ';
            }
        }else{
            $priorCheckPassed = false;
            $newReport = $this->initReport($machine, $script->script, $backup, null, "CRITICAL", "");
            $priorCheckError .= 'Details about the scheduled backup could not be found in database. ';
        }

        $admin = $this->adminRepo->find(1);
        if($priorCheckPassed === false){
            if($this->canNotify($admin, $newReport) === true){
                $sessionMan = $this->container->get('session_handler');
                $sessionMan->sendEmailNotification($admin->email, $newReport);
            }
            return array('success'=>false, 'error'=>$priorCheckError, 'report'=>$newReport);
        }

        
        
        $secureParam = $backup->isSecureParameters;
        if($secureParam == null){
            $secureParam = false;
        }
        if($command == null){
            $command = "";
        }
        $newReport = null;

        // Checking if we can reach the host
        $isReachable = $this->isHostReachable($machine);
        if($isReachable === false){
            $newReport = $this->initReport($machine, $script->script, $backup, false, "CRITICAL", "The host could not be reached");
            return array('success'=>false, 'error'=>'The host could not be reached', 'report'=>$newReport);
        }

        $login = $this->attemptSshLogin($machine);
        if($login['success'] === true){
            $ssh = $login['ssh'];

            // Create backupsystem folder in tmp on remote machine  
            ob_start();
                $dumb = $ssh->exec('mkdir /tmp/backupsystem');
            ob_end_clean();

            // Create new Report
            $scp = new NetScp($ssh);
            $newReport = $this->initReport($machine,$script->script, $backup, null, "RUNNING", "");

            // Send script to server
            $tmpName = $this->getRandomName(12);
            $success = $scp->put("/tmp/backupsystem/$tmpName", $script->script);

            // Update report in db & execute Script
            if($success === true){
                if($secureParam === true){
                    $scp->put("/tmp/backupsystem/$tmpName.args", $command);
                }
                $newReport->pathScriptFile = "/tmp/backupsystem/".$tmpName;
                $this->em->persist($newReport);
                $this->em->flush();
                $this->executeTmpScriptAndWriteOutput($ssh, $newReport, $command, $secureParam);
                $this->watchProcesses();                
            }else{
                $newReport->isSuccess = false;
                $newReport->status = "CRITICAL";
                $newReport->details = "Error: Script could not be sent to /tmp/backupsystem/$tmpName";
                $this->em->persist($newReport);
                $this->em->flush();
                $ssh->disconnect();
                return array('success'=>false, 'error'=>"Error: Script could not be sent to /tmp/backupsystem/$tmpName", 'report'=>$newReport);
            }
        }else{
            return $login;
        }
        $ssh->disconnect();
        return array('success'=>true, 'error'=>'', 'report'=>$newReport);
    }

    public function executeTmpScriptAndWriteOutput($ssh, $report, $command, $secureParam){
        $pathTmpScript = $report->pathScriptFile;
        $ssh->exec("chmod 700 $pathTmpScript;");

        if($secureParam === true){
            $ssh->exec("chmod 700 $pathTmpScript.args;");
        }

        $exec = "touch $pathTmpScript.out;chmod 600 $pathTmpScript.out;";
        $exec .= "echo \"===BEGIN PROCESS===\n\">>$pathTmpScript.out;";

        if($secureParam === true){
            $exec .= "$pathTmpScript $pathTmpScript.args >>$pathTmpScript.out;";
        }else{
            if(empty($command)){
                $exec .= "$pathTmpScript >> $pathTmpScript.out;";
            }else{
                $exec .= "$pathTmpScript ".escapeshellarg($command).">>$pathTmpScript.out;";
            }
            
        }
        $exec .= "echo \"\n===END PROCESS===\">>$pathTmpScript.out";

        return $ssh->exec($exec, false);
    }

    public function watchProcesses()
    {
        $rootDir = $this->container->get('kernel')->getRootDir();
        $reportRepo = $this->em->getRepository('BackSystSystemBundle:Report');
        $backupRepo = $this->em->getRepository('BackSystSystemBundle:Backup');
        $processRepo = $this->em->getRepository('BackSystSystemBundle:WatchProcess');
        $runningScripts = $reportRepo->findBy(array('status'=>'RUNNING'));

        if($runningScripts != null){
            foreach ($runningScripts as $report) {
                $reportId = $report->id;
                $process = $processRepo->findBy(array('report'=>$report, 'isDone'=>false)); // There shou
                if($process != null){
                    // Check if process is currently running
                    $pid = $process->pid;
                    $out = exec("ps cax | grep $pid");
                    if(empty($out) || $out == "" || $out == null){ // No process running
                        $process->isDone = true;
                        $process->isSuccess = false;
                        $this->em->persist($process);
                        $this->em->flush();

                        $newProcess = new WatchProcess();
                        $newProcess->pid = exec("nohup php $rootDir/console backsyst:backups:watch $reportId >/dev/null 2>&1 & echo $!");
                        //$this->watchProcess($reportId); $report->pid = 12;
                        $newProcess->report = $report;

                        $backup = $backupRepo->findOneBy(array('uuid'=>$report->backupOriginalId));

                        $newProcess->backup = $backup;
                        if($backup == null){
                            $newProcess->isDone = true;
                            $newProcess->isSuccess = false;
                            $report->details = "Error: Backup no longer exists";
                            $report->isSuccess = false;
                        }

                        $this->em->persist($newProcess);
                        $this->em->persist($report);
                        $this->em->flush();
                    }
                }else{
                    $newProcess = new WatchProcess();
                    $newProcess->pid = exec("nohup php $rootDir/console backsyst:backups:watch $reportId >/dev/null 2>&1 & echo $!");
                    //$this->watchProcess(40); $report->pid = $newProcess->pid;
                    $newProcess->report = $reportRepo->find($reportId);

                    $backup = $backupRepo->findOneBy(array('uuid'=>$report->backupOriginalId));
                    $newProcess->backup = $backup;

                    if($backup == null){
                        $newProcess->isDone = true;
                        $newProcess->isSuccess = false;
                        $report->details = "Error: Backup no longer exists";
                        $report->isSuccess = false;
                    }

                    $this->em->persist($newProcess);
                    $this->em->persist($report);
                    $this->em->flush();
                }                
            }
        }
    }

    // This is meant to run in background
    public function watchProcess($reportId)
    {
        exec('echo "Starting Watch" >> /tmp/backup_debug.txt');
        $machineRepo = $this->em->getRepository('BackSystSystemBundle:RemoteMachine');
        $backupRepo = $this->em->getRepository('BackSystSystemBundle:Backup');
        $reportRepo = $this->em->getRepository('BackSystSystemBundle:Report');
        
        $report = $reportRepo->find($reportId);
        exec('echo "Report found" >> /tmp/backup_debug.txt');
        $machine = $machineRepo->findOneBy(array('uuid'=>$report->machineOriginalId));
        $backup = $backupRepo->findOneBy(array('uuid'=>$report->backupOriginalId));

        $admin = $this->em->getRepository('BackSystSystemBundle:Admin')->find(1);

        // Checking if we can reach the host
        $isReachable = $this->isHostReachable($machine);
        if($isReachable === false){
            $this->initReport($machine, $script->script, $backup, false, "The host could not be reached");
            return array('success'=>false, 'error'=>'The host could not be reached');
        }

        $login = $this->attemptSshLogin($machine);
        
        if($login['success'] === true){
            $ssh = $login['ssh'];
            $it = 0;

            while(true){
                $tmpScript = $report->pathScriptFile;
                $tmpOut = "$tmpScript.out";
                $lastLine = $ssh->exec("tail -n1 $tmpOut", true);

                if(strpos($lastLine, "===END PROCESS===") !== false){
                    $report->output = $ssh->exec("cat $tmpOut", true);
                    $ssh->exec("rm -rf $tmpOut", true);
                    $ssh->exec("rm -rf $tmpScript", true);

                    if($report->isSecureParameters === true){
                        $ssh->exec("rm -rf $tmpScript.args", true);
                    }

                    $report->status = "Analyzing Output";
                    $report->endedAt = new \DateTime();
                    
                    $watchProcess = $this->getWatchingProcess($report);
                    $process = null;
                    if($watchProcess['success'] === true){
                        $process = $watchProcess['process'];
                        $process->isDone = true;
                        $process->isSuccess = true;
                        $process->endedAt = new \DateTime();
                        $this->em->persist($process);
                    }

                    $this->em->persist($report);
                    $this->em->flush();
                    $this->analyzeReport($report);
                    $ssh->disconnect();
                    
                    if($this->canNotify($admin, $report) === true){
                        $sessionMan = $this->container->get('session_handler');
                        $sessionMan->sendEmailNotification($admin->email, $report);
                    }
                    if($process != null){
                        exec("kill ".$process->pid);
                    }
                    
                    return true; // Should end the process normally
                }else{
                    if($it++%100 == 0){
                        $report->output = $ssh->exec("cat $tmpOut", true);
                        $this->em->persist($report);
                        $this->em->flush();
                    }
                }
            }
        }
    }

    public function getWatchingProcess($report)
    {
        $processRepo = $this->em->getRepository('BackSystSystemBundle:WatchProcess');
        $process = $processRepo->findBy(array('report'=>$report));

        if($process != null){
            $pid = $process->pid;
            return array('success'=>false, 'process'=>$process);
        }else{
            return array('success'=>false, 'error'=>"Error: Watching process could not be found");
        }


    }

    public function attemptSshLogin($machine)
    {
        define('NET_SSH2_LOGGING', NET_SSH2_LOG_SIMPLE);

        $admin = $this->adminRepo->find(1);
        $ssh = new NetSsh2($machine->host);
        $key = new CryptRsa();
        $key->loadKey($admin->privateKey);

        $publicHostKey = $ssh->getServerPublicHostKey();
        $existingHost = $this->knownHostsRepo->findOneBy(array('rsaPublicKey'=>$publicHostKey));
        if($existingHost == null){
            $hostIp = $this->getIpAddress($machine->host);
            return array('success'=>false, 'error'=>'Error: The host is unknown.', 'hostIp'=>$hostIp, 'publicKey'=>$publicHostKey, 'machine'=>$machine->id, 'host'=>$machine->host);
        }

        if (!$ssh->login($machine->user, $key)){
            $log = $ssh->getLog();
            unset($log[0]); unset($log[1]);
            $machine->isTested = true;
            $machine->isTestSuccess = false;
            $this->em->persist($machine);
            $this->em->flush();
            return array('success'=>false, 'error'=>'Access denied. Check that your username and private key are both correct', 'logs'=>$log);
        }else{
            $machine->isTested = true;
            $machine->isTestSuccess = true;
            $this->em->persist($machine);
            $this->em->flush();
            $log = $ssh->getLog();
            unset($log[0]); unset($log[1]);
            return array('success'=>true, 'ssh'=>$ssh, 'error'=>'', 'logs'=>$log);
        }
    }

    public function getRandomName($length)
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    public function initReport($machine, $script="", $backup, $success=null, $status="", $details="")
    {
        $newReport = new Report();
        if($machine != null){
            $newReport->machineOriginalId = $machine->uuid;
            $newReport->machineName = $machine->name;
            $newReport->machineUser = $machine->user;
            $newReport->machineHost = $machine->host;
        }else{
            $newReport->machineOriginalId = "COULD NOT BE RETRIEVED";
            $newReport->machineName = "COULD NOT BE RETRIEVED";
            $newReport->machineUser = "COULD NOT BE RETRIEVED";
            $newReport->machineHost = "COULD NOT BE RETRIEVED";
            $newReport->details .= "Remote machine could not be retrieved from Database. ";
        }
        
        if($backup != null){
            $newReport->commandLine = $backup->commandLine;
            $newReport->notification = $backup->notification;
            $newReport->isSecureParameters = $backup->isSecureParameters;
            $newReport->backupOriginalId = $backup->uuid;
            $newReport->backupName = $backup->title;
        }else{
            $newReport->commandLine = "COULD NOT BE RETRIEVED";
            $newReport->notification = "COULD NOT BE RETRIEVED";
            $newReport->isSecureParameters = "COULD NOT BE RETRIEVED";
            $newReport->backupOriginalId = "COULD NOT BE RETRIEVED";
            $newReport->backupName = "COULD NOT BE RETRIEVED";
            $newReport->details .= "Backup could not be retrieved from Database. ";
        }
        
        $newReport->script = $script;
        $newReport->startedAt = new \DateTime();
        $newReport->details = $details;
        $newReport->isSuccess = false;
        $newReport->status = $status;

        $this->em->persist($newReport);
        $this->em->flush();
        return $newReport;
    }

    public function isHostReachable($machine)
    {
        // Checking if host is reachable
        $resp = null;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $machine->host);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        ob_start();
            $resp = curl_exec($ch);
            curl_close($ch);
        ob_end_clean();
        return $resp;
    }

    // public function extractArguments($command)
    // {
    //     //preg_split();
    //     $hello = "$arg1$ $arg2$"
    // }

    public function analyzeReport($report)
    {
        $output = $report->output;
        $lines = explode("\n", $output);
        $last = "";

        // $i = 1 should point to ===END PROCESS===
        // $i = 2 should point to lines preceding ===END PROCESS===
        for($i = 1; $i < count($lines); $i++){
            $last = $lines[count($lines)-$i];
            if(!empty($last)){
                if($last == "===END PROCESS==="){
                    // keep looping
                }else{
                    $last = $lines[count($lines)-$i];
                    break;
                }
            }
        }
        unset($lines);
        //$decomposed = explode('\t', $last);
        $decomposed = preg_split("/[\t]|[\\t]/", $last);
        print_r($decomposed);
        $report->endedAt = new \DateTime();
        if(count($decomposed) > 0){
            if(preg_replace('/\s+/', '', strtolower($decomposed[0])) == "ok"){
                $report->isSuccess = true;
                $report->status = "Success";
                if(count($decomposed) >= 4){
                    $report->destination = $decomposed[1];
                    $report->size = $decomposed[2];
                    $report->details = $decomposed[3];
                    for($i = 4; $i < count($decomposed); $i++){
                        $report->details .= $decomposed[$i];
                    }
                }else{
                    $report->isSuccess = false;
                    $report->status = "WARNING";
                    $report->details = "The content of the summary line at the end of the output does not seem correct. The backup may have been successfully saved, However we're not able to confirm it.";
                }
            }else{
                $report->isSuccess = false;
                if(preg_replace('/\s+/', '', strtolower($decomposed[0])) == "error" || preg_replace('/\s+/', '', strtolower($decomposed[0])) == "critical"){
                    $report->status = "CRITICAL";
                }else{
                    $report->status = "WARNING";
                }
                if(count($decomposed) >= 2){
                    $report->details = $decomposed[1];
                    for($i = 2; $i < count($decomposed); $i++){
                        $report->details .= $decomposed[$i];
                    }
                }else{
                    $report->details = "No details available";
                }
            }
        }else{
            $report->details = "The content of the summary line at the end of the output does not seem correct. The backup may have been successfully saved, However we're not able to confirm it.";
            $report->isSuccess = false;
            $report->status = "CRITICAL";
        }
        $this->em->persist($report);
        $this->em->flush();
    }

    public function getAllBackupsToday()
    {
        $backupRepo = $this->em->getRepository('BackSystSystemBundle:Backup');
        $backups = $backupRepo->findAll();

        $currentTime = new \DateTime();
        $days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
        $today = $days[$currentTime->format('w')];
        $time = $currentTime->format('h:i');

        $output = array();
        if($backups != null){
            foreach ($backups as $backup) {
                if(strpos($backup->schedule, $today) !== false){
                    $output[] = $backup;
                }
            }
        }
        return $output;
    }

    public function getBackupsToBeExecutedNow()
    {
        $backupRepo = $this->em->getRepository('BackSystSystemBundle:Backup');
        $backups = $backupRepo->findAll();

        // Find out day & time
        $currentTime = new \DateTime();
        $days = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
        $today = $days[$currentTime->format('w')];
        $time = $currentTime->format('h:i');

        $output = array();
        if($backups != null){
            foreach ($backups as $backup) {
                if($backup->isActive === true){
                   if(strpos($backup->schedule, $today) !== false){
                        if(strpos($backup->schedule, $time) !== false){
                            $output[] = $backup;
                        }
                    } 
                }
            }
        }

        return $output;
    }

    public function isValidPrivateKey($key)
    {
        $rsa = new CryptRsa();
        $rsa->loadKey($key);
        if($rsa->getPrivateKey() === false){
            return false;
        }
        return true;
    }

    public function canNotify($admin, $report)
    {
        if($admin->email != null && $admin->email != ""){
            $notifications = $this->readNotifications($report->notification);
            if(strtolower($report->status) == "success" ||  strtolower($report->status) == "ok"){
                $search = array_search("4", $notifications);
            }else if(strtolower($report->status) == "warning"){
                $search = array_search("2", $notifications);
            }else if(strtolower($report->status) == "error" ||  strtolower($report->status) == "critical"){
                $search = array_search("1", $notifications);

            }
            if($search !== false){
                return true;
            }
            return false;
        }
    }

    public function readNotifications($n){
        $output = array();
        if($n == 0){
            return $output;
        }
        if($n % 2 != 0){
            $output[] = 1;
            $n = $n - 1;
        }
        if($n >= 4){
            $output[] = 4;
            $n = $n - 4;
        }
        if($n == 2){
            $output[] = 2;
        }
        return $output;
    }

}

?>