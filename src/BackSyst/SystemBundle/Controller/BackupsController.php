<?php

/**
 *  Author: Nicolas Dijoux
 *  Year: 2014
 *  Organisation: Locatrix Communication
 *  Copyright: Locatrix Communication - All rights reserved
 *
 */

namespace BackSyst\SystemBundle\Controller;

use BackSyst\SystemBundle\Entity\Script;
use BackSyst\SystemBundle\Entity\RemoteMachine;
use BackSyst\SystemBundle\Entity\Backup;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;

class BackupsController extends Controller implements TokenAuthenticatedController
{
	public function indexAction($success = true, $errors = "")
	{
		$errorsArr = array();
		if(strstr($errors, 'noserv') !== false){
			$errorsArr[] = "Error: You have not created any machine yet. Please do so to be able to create a new backup";
		}
		if(strstr($errors, 'noscript') !== false){
			$errorsArr[] = "Error: You have not created any script yet. Please do so to be able to create a new backup";
		}

		$em = $this->getDoctrine()->getManager();
		$backupRepo = $em->getRepository('BackSystSystemBundle:Backup');
		$backups = $backupRepo->findAll();

		$backupsOutput = array();
		foreach ($backups as $b) {
			$backupsOutput[] = array('id'=>$b->id, 'title'=>$b->title, 'description'=>$b->description);
		}

		return $this->render('BackSystSystemBundle:Backups:backups.html.twig', array('backups'=>$backupsOutput, 'success'=>$success, 'errors'=>$errorsArr));
	}

	public function addAction()
	{
		$success = true;
		$errors = "";

		$mAndS = $this->getMachinesAndScripts();

		if(empty($mAndS['machines'])){
			$success = false;
			$errors .= "noserv";
		}

		if(empty($mAndS['scripts'])){
			$success = false;
			$errors .= "noscript";
		}

		if($success === true){
			$backup = array('title'=>"", 'scripts'=>$mAndS['scripts'], 'servers'=>$mAndS['machines'], 'script'=>"", 'server'=>"", 'description'=>"", 'days'=>array(), 'hour'=>"", 'minute'=>"");
			return $this->render('BackSystSystemBundle:Backups:new.html.twig', array('backup'=>$backup));
		}else{
			return $this->redirect($this->generateUrl('list_backups', array('success'=>0, 'errors'=>$errors)));
		}
	}

	public function newAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$serverRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
		$scriptRepo = $em->getRepository('BackSystSystemBundle:Script');
		$adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
		$admin = $adminRepo->find(1);

		$success = true;
		$errors = array();

		$mAndS = $this->getMachinesAndScripts();
		$scriptsId = $mAndS['scriptsId'];
	    $machinesId = $mAndS['machinesId'];

		$days = array('mon'=>'Monday', 'tue'=>'Tuesday', 'wed'=>'Wednesday', 'thu'=>'Thursday', 'fri'=>'Friday', 'sat'=>'Saturday', 'sun'=>'Sunday');
		$notifications = array(4=>"OK", 2=>"WARNING", 1=>"CRITICAL");
		$note = 0;

		$backup = new Backup();

		$form = $this->createFormBuilder($backup)
					->add('title')
					->add('script', 'choice', array('choices'=>$mAndS['scripts'], 'mapped'=>false, 'required'=>true, 'preferred_choices'=>array(0)))
					->add('commandLine')
					->add('server', 'choice', array('choices'=>$mAndS['machines'], 'mapped'=>false, 'preferred_choices'=>array(0)))
					->add('days', 'choice', array('choices'=>$days, 'multiple'=>true, 'expanded'=>true, 'mapped'=>false, 'required'=>true))
					->add('time', 'time', array('widget'=>'text', 'mapped'=>false))
					->add('description', 'textarea', array('required'=>false))
					->add('isActive')
					->add('notifications', 'choice', array('choices'=>$notifications, 'multiple'=>true, 'expanded'=>true, 'mapped'=>false, 'required'=>false))
					->add('isSecureParameters', 'choice', array('choices'=>array(0, 1), 'multiple'=>false, 'expanded'=>true, 'mapped'=>false, 'required'=>true, 'data'=>"0"))
					->add('button', 'submit', array('label'=>'Save'))
					->getForm();

		$form->handleRequest($request);

		if($form->isSubmitted())
		{
			if($form->get('script')->getData() <= 0){
                $form->get('script')->addError(new FormError('You must select a script.'));
            }
            if($form->get('server')->getData() <= 0){
                $form->get('server')->addError(new FormError('You must select a remote machine.'));
            }
            $days = $form->get('days')->getData();
            if(empty($days)){
                $form->get('days')->addError(new FormError('You must select at least one day.'));
            }

            $notifs = $form->get('notifications')->getData();
            foreach ($notifs as $n) {
            	$note += $n;
            }
		}

        if($form->isValid())
        {
        	$machineIndex = $form->get('server')->getData();
        	$scriptIndex = $form->get('script')->getData();

        	$scriptsId = $mAndS['scriptsId'];
        	$machinesId = $mAndS['machinesId'];

        	$script = $scriptRepo->findOneBy(array('uuid'=>$scriptsId[$scriptIndex]));
        	$machine = $serverRepo->findOneBy(array('uuid'=>$machinesId[$machineIndex]));

        	$time = $form->get('time')->getData()->format('h:i');
        	$days = $form->get('days')->getData();
        	$schedule = $this->stringifyRecurrence($days, $time);
        	
        	$backup->schedule = $schedule;
        	$backup->script = $script;
        	$backup->machine = $machine;
        	$backup->notification = $note;

        	$secChoice = $form->get('isSecureParameters')->getData();
        	if($secChoice == 0){
        		$backup->isSecureParameters = false;
        	}else{
        		$backup->isSecureParameters = true;
        	}

        	$em->persist($backup);

        	$em->flush();
        	return $this->redirect($this->generateUrl('list_backups'));
        }

        if($admin->email == null || $admin->email == ""){
        	return $this->render('BackSystSystemBundle:Backups:new.html.twig',  array('form'=>$form->createView(), 'noemail'=>true));
        }

        return $this->render('BackSystSystemBundle:Backups:new.html.twig',  array('form'=>$form->createView()));
		
	}

	public function pauseAction($id)
	{
		return $this->render('BackSystSystemBundle:Backups:new.html.twig');
	}

	public function activateAction($id)
	{
		return $this->render('BackSystSystemBundle:Backups:new.html.twig');
	}

	public function editInfoPageAction($id = null, $triedRun = false)
	{
		$em = $this->getDoctrine()->getManager();
        $backupRepo = $em->getRepository('BackSystSystemBundle:Backup');
        $scriptRepo = $em->getRepository('BackSystSystemBundle:Script');
        $serverRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
        $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
		$admin = $adminRepo->find(1);

        $backup = $backupRepo->find($id);

        if($backup != null){
        	$days = array('mon'=>'Monday', 'tue'=>'Tuesday', 'wed'=>'Wednesday', 'thu'=>'Thursday', 'fri'=>'Friday', 'sat'=>'Saturday', 'sun'=>'Sunday');
        	$notifications = array(4=>"OK", 2=>"WARNING", 1=>"CRITICAL");

        	$mAndS = $this->getMachinesAndScripts();
        	$scriptsId = $mAndS['scriptsId']; unset($scriptsId[0]);
	        $machinesId = $mAndS['machinesId']; unset($machinesId[0]);

	        $schedule = $this->readSchedule($backup->schedule);
	        $currentNotifs = $this->readNotifications($backup->notification);
	        $note = 0;

	        $secChoice = 0;
	        if($backup->isSecureParameters === true){
	        	$secChoice = 1;
	        }

	        $scriptChoice = array(0);
	        if($backup->script != null){
	        	unset($mAndS['scripts'][0]);
	        	$scriptChoice = array(array_search($backup->script->uuid, $scriptsId));
	        }
	        
	        $machineChoice = array(0);
	        if($backup->machine != null){
	        	unset($mAndS['machines'][0]);
	        	$machineChoice = array(array_search($backup->machine->uuid, $machinesId));
	        }

			$form = $this->createFormBuilder($backup)
					->add('title')
					->add('script', 'choice', array('choices'=>$mAndS['scripts'], 'mapped'=>false, 'required'=>true, 'preferred_choices'=>$scriptChoice))
					->add('commandLine')
					->add('server', 'choice', array('choices'=>$mAndS['machines'], 'mapped'=>false, 'preferred_choices'=>$machineChoice))
					->add('days', 'choice', array('choices'=>$days, 'multiple'=>true, 'expanded'=>true, 'mapped'=>false, 'required'=>true, 'data'=>$schedule['days']))
					->add('time', 'time', array('widget'=>'text', 'mapped'=>false, 'data'=>$schedule['time']))
					->add('description', 'textarea', array('required'=>false))
					->add('isActive')
					->add('notifications', 'choice', array('choices'=>$notifications, 'multiple'=>true, 'expanded'=>true, 'mapped'=>false, 'required'=>false, 'data'=>$currentNotifs))
					->add('isSecureParameters', 'choice', array('choices'=>array(0, 1), 'multiple'=>false, 'expanded'=>true, 'mapped'=>false, 'required'=>true, 'data'=>$secChoice))
					->add('button', 'submit', array('label'=>'Save'))
					->getForm();

			$form->handleRequest($this->container->get('request'));

			if($form->isSubmitted())
			{
				if($form->get('script')->getData() <= 0){
	                $form->get('script')->addError(new FormError('You must select a script.'));
	            }
	            if($form->get('server')->getData() <= 0){
	                $form->get('server')->addError(new FormError('You must select a remote machine.'));
	            }
	            $days = $form->get('days')->getData();
	            if(empty($days)){
	                $form->get('days')->addError(new FormError('You must select at least one day.'));
	            }

	            $notifs = $form->get('notifications')->getData();
	            foreach ($notifs as $n) {
	            	$note += $n;
	            }
			}

	        if($form->isValid())
	        {
	        	$machineIndex = $form->get('server')->getData();
	        	$scriptIndex = $form->get('script')->getData();

	        	$script = $scriptRepo->findOneBy(array('uuid'=>$scriptsId[$scriptIndex]));
        		$machine = $serverRepo->findOneBy(array('uuid'=>$machinesId[$machineIndex]));

	        	$time = $form->get('time')->getData()->format('h:i');
	        	$days = $form->get('days')->getData();
	        	$schedule = $this->stringifyRecurrence($days, $time);
	        	
	        	$backup->schedule = $schedule;
	        	$backup->script = $script;
	        	$backup->machine = $machine;
	        	$backup->notification = $note;

	        	$secChoice = $form->get('isSecureParameters')->getData();
	        	if($secChoice == 0){
	        		$backup->isSecureParameters = false;
	        	}else{
	        		$backup->isSecureParameters = true;
	        	}

	        	$em->persist($backup);
	        	$em->flush();
	        	return $this->redirect($this->generateUrl('list_backups'));
	        }

	        $emailError = false;
	        if($admin->email == null || $admin->email == ""){
	        	$emailError = true;
	        }

	        if($triedRun == 1 || $triedRun === true){
	        	return $this->render('BackSystSystemBundle:Backups:update.html.twig',  array('form'=>$form->createView(), 'noemail'=>$emailError, 'backup'=>array('id'=>$backup->id), 'errorTest'=>true));
	        }else{
	        	return $this->render('BackSystSystemBundle:Backups:update.html.twig',  array('form'=>$form->createView(), 'noemail'=>$emailError, 'backup'=>array('id'=>$backup->id)));
	        }
			
        }

        die("Error: This backup does not exist");
	}

	public function runAction($id = 0)
	{
		if($id == 0 || $id == null){
			die('Error: Backup does not exist');
		}
		$em = $this->getDoctrine()->getManager();
        $backupRepo = $em->getRepository('BackSystSystemBundle:Backup');
        $backup = $backupRepo->find($id);

        if($backup != null){
        	if($backup->isActive === true){
        		$sshMan = $this->get('ssh_connect');
				$resp = $sshMan->runScript($backup, $backup->machine, $backup->commandLine);
				return $this->redirect($this->generateUrl('detail_report', array('id'=>$resp['report']->id)));
			}else{
				return $this->redirect($this->generateUrl('edit_info_backup', array('id'=>$backup->id, 'triedRun'=>1)));
			}
        }
        die('Error: This backup does not exist, sorry.');
		
	}

	public function updateAction(Request $request)
	{
		return $this->render('BackSystSystemBundle:Backups:new.html.twig');
	}

	public function deleteAction($id=0)
	{
		if($id == 0 || $id == null){
			die('Error: Backup does not exist');
		}
		$em = $this->getDoctrine()->getManager();
        $backupRepo = $em->getRepository('BackSystSystemBundle:Backup');
        $backup = $backupRepo->find($id);

        if($backup != null){
			$em->remove($backup);
			$em->flush();
			return $this->redirect($this->generateUrl('list_backups'));
        }
        die('Error: This backup does not exist, sorry.');
	}

	public function validateAndReturnTime($hour, $min)
	{
		if(!is_numeric($hour) || !is_numeric($hour)){
			return array('success'=>false, 'error'=>"Time values must be numerical values");
		}else{
			if($hour < 0 || $hour > 23){
				return array('success'=>false, 'error'=>"Hours must be between 0 and 23");
			}else if($min < 0 || $min > 59){
				return array('success'=>false, 'error'=>"Minutes must be between 0 and 59");
			}else{
				return array('success'=>true, 'time'=>$hour.":".$min);
			}
		}
	}

	public function readSchedule($schedule)
	{
		$schedule = split(",", $schedule);
		$time = new \DateTime($schedule[count($schedule)-1]);
		unset($schedule[count($schedule)-1]);

		return array('days'=>$schedule, 'time'=>$time);
	}

	public function stringifyRecurrence($days, $time)
	{
		$string = "";
		foreach ($days as $day) {
			$string .= $day .",";
		}
		$string .= $time;
		return $string;
	}

	public function getMachinesAndScripts()
	{
		$em = $this->getDoctrine()->getManager();
		$scriptRepo = $em->getRepository('BackSystSystemBundle:Script');
		$machineRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
		$scripts = $scriptRepo->findAll();
		$machines = $machineRepo->findAll();

		$scriptsIdOutput = array(0);
		$machinesIdOutput = array(0);

		$scriptsOutput = array('Select a script');
		$machinesOutput = array('Select a server');

		foreach ($scripts as $s) {
			$scriptsIdOutput[] = $s->uuid;
			$scriptsOutput[] = $s->title;
		}
		foreach ($machines as $m) {
			$machinesIdOutput[] = $m->uuid;
			$machinesOutput[] = $m->name;
		}

		return array('success'=>true, 'machinesId'=>$machinesIdOutput, 'machines'=>$machinesOutput, 'scriptsId'=>$scriptsIdOutput, 'scripts'=>$scriptsOutput);
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

	public function testAction(){
		$id = 8;
		$em = $this->getDoctrine()->getManager();
		
		$sshman = $this->get('ssh_connect');
		$sshman->watchProcess(7);

		return $this->render('BackSystSystemBundle:Backups:test.html.twig');
	}
}