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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;

class ScriptsController extends Controller implements TokenAuthenticatedController
{
	public function indexAction()
	{
		$em = $this->getDoctrine()->getManager();
        $scriptRepo = $em->getRepository('BackSystSystemBundle:Script');

        $scripts = $scriptRepo->findAll();
        $output = array();
        foreach ($scripts as $script) {
        	$output[] = array('id'=>$script->id, 'title'=>$script->title, 'description'=>$script->description, 'created_at'=>$script->createdAt);
        }
        return $this->render('BackSystSystemBundle:Scripts:list.html.twig', array('scripts'=>$output));
	}

	public function editAction($id = null)
	{
		$em = $this->getDoctrine()->getManager();
		$scriptsRepo = $em->getRepository('BackSystSystemBundle:Script');
		$script = null;
		$isIdError = false;
		$templ = 'BackSystSystemBundle:Scripts:update.html.twig';

		if($id != null && $id != 0){
			$script = $scriptsRepo->find($id);
			if($script == null) $isIdError = true;
		}

		if($script == null){
			$script = New Script();
			$templ = 'BackSystSystemBundle:Scripts:new.html.twig';
		}
		
		$form = $this->createFormBuilder($script)
					->add('title', 'text', array('required'=>true))
					->add('script', 'textarea', array('required'=>true))
					->add('description', 'textarea', array('required'=>false))
					->add('button', 'submit', array('label'=>'Save'))
					->getForm();

		$form->handleRequest($this->container->get('request'));

        if($form->isValid())
        {
        	if($script->description == null)
        		$script->description = "";
        	
        	$script->script = str_ireplace("\x0D", "", $script->script);
        	$em->persist($script);
        	$em->flush();
        	return $this->redirect($this->generateURL('list_scripts'));
        }

        if($isIdError){
        	return $this->render($templ, array('form'=>$form->createView(), 'error'=>'The selected script does not exist in the database'));
        }else{
        	return $this->render($templ, array('form'=>$form->createView(), 'script'=>array('id'=>$script->id)));
        }
	}

	public function deleteAction($id = null)
	{
		if(!$id){
			die('No script specified');
		}
		$em = $this->getDoctrine()->getManager();
        $scriptRepo = $em->getRepository('BackSystSystemBundle:Script');
        $backupRepo = $em->getRepository('BackSystSystemBundle:Backup');

		$script = $scriptRepo->find($id);
		$backups = $backupRepo->findBy(array('script'=>$script));
		foreach ($backups as $b) {
			$b->script = null;
			$b->isActive = false;
		}
		if($script != null){
			$em->remove($script);
			$em->flush();
			return $this->redirect($this->generateUrl('list_scripts'));
		}
		die('Error: The script could not be found');
	}

}