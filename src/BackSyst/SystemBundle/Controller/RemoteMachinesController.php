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
use BackSyst\SystemBundle\Entity\Backup;
use BackSyst\SystemBundle\Entity\RemoteMachine;
use BackSyst\SystemBundle\Entity\SSHKnownHosts;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class RemoteMachinesController extends Controller implements TokenAuthenticatedController
{
	public function indexAction()
	{
		$em = $this->getDoctrine()->getManager();
		$machinesRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
		$machines = $machinesRepo->findAll();

		$list = array();
		foreach ($machines as $machine) {
			$isProblem = false;
			if($machine->isTested === false || $machine->isTestSuccess === false){
				$isProblem = true;
			}
			$list[] = array('id'=>$machine->id, 'name'=>$machine->name, 'user'=>$machine->user, 'host'=>$machine->host, 'description'=>$machine->description, 'isProblem'=>$isProblem);
		}
		return $this->render('BackSystSystemBundle:RemoteMachines:machines.html.twig', array('machines'=>$list));
	}

	public function editAction($id = null)
	{
		$em = $this->getDoctrine()->getManager();
		$machinesRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
		$machine = null;
		$isIdError = false;
		$templ = 'BackSystSystemBundle:RemoteMachines:update.html.twig';

		if($id != null && $id != 0){
			$machine = $machinesRepo->find($id);
			if($machine == null) $isIdError = true;
		}

		if($machine == null){
			$machine = New RemoteMachine();
			$templ = 'BackSystSystemBundle:RemoteMachines:new.html.twig';
		}
		
		$form = $this->createFormBuilder($machine)
				->add('name', 'text', array('required'=>true))
				->add('user', 'text', array('required'=>true))
				->add('host', 'text', array('required'=>true))
				->add('description', 'textarea', array('required'=>false))
				->add('button', 'submit', array('label'=>'Save'))
				->getForm();

		$form->handleRequest($this->container->get('request'));

		if($form->isSubmitted()){
			if($form->get('description')->getData() == null)
        		$machine->description = "";
		}

        if($form->isValid())
        {
        	$machine->isTested = false;
        	$machine->isTestSuccess = false;
        	$em->persist($machine);
        	$em->flush();
        	return $this->testConnectionAction($machine->id);
        	//return $this->redirect($this->generateURL('list_machines'));
        }

        if($isIdError){
        	return $this->render($templ, array('form'=>$form->createView(), 'error'=>'The selected machine does not exist in the database'));
        }else{
        	return $this->render($templ, array('form'=>$form->createView(), 'machine'=>array('id'=>$machine->id)));
        }
	}

	public function deleteAction($id = null)
	{
		if($id == null){
			die('Error: There is no machine selected.');
		}

		$em = $this->getDoctrine()->getManager();
		$machinesRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
		$backupRepo = $em->getRepository('BackSystSystemBundle:Backup');

		$machine = $machinesRepo->find($id);

		$backups = $backupRepo->findBy(array('machine'=>$machine));
		foreach ($backups as $b) {
			$b->machine = null;
			$b->isActive = false;
		}

		if($machine != null){
			$em->remove($machine);
			$em->flush();
			return $this->redirect($this->generateURL('list_machines'));
		}
		die("Error: This machine does not exist.");
	}

	public function testConnectionAction($id = null)
	{
		if($id != null){
			$em = $this->getDoctrine()->getManager();
			$machinesRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
			$machine = $machinesRepo->find($id);

			if($machine != null){
				$sshMan = $this->get('ssh_connect');
				$resp = $sshMan->testConnection($machine);
				$output = array('id'=>$machine->id, 'name'=>$machine->name, 'host'=>$machine->host, 'user'=>$machine->user, 'fromRoute'=>'list_machines');

				if(!isset($resp['publicKey'])){
					$machine->isTested = true;
					if($resp['success'] === true){
						$machine->isTestSuccess = true;
					}else{
						$machine->isTestSuccess = false;
					}
					return $this->render('BackSystSystemBundle:RemoteMachines:results_test.html.twig', array('success'=>$resp['success'], 'machine'=>$output, 'error'=>$resp['error'], 'logs'=>$resp['logs']));
				}else{
					return $this->render('BackSystSystemBundle:Security:first_time_connect_confirmation.html.twig', array_merge(array(	'machineId'=>$machine->id, 'fromRoute'=>'list_machines', 'successRoute'=>'test_connection_machine' ), $resp ));
				}
			}
			die('Error: This machine does not exist.');
		}
		die('Error: This machine does not exist.');
		return $this->render('BackSystSystemBundle:RemoteMachines:results.html.twig');
	}

	public function isValidHost($host)
	{
		// some validation code here
		return true;
	}
}