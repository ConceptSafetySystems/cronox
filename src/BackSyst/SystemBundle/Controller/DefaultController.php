<?php

/**
 *  Author: Nicolas Dijoux
 *  Year: 2014
 *  Organisation: Locatrix Communication
 *  Copyright: Locatrix Communication - All rights reserved
 *
 */

namespace BackSyst\SystemBundle\Controller;

use HRPortal\SystemBundle\Entity\Admin;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;

class DefaultController extends Controller implements TokenAuthenticatedController
{
    public function indexAction()
    {
        return $this->render('BackSystSystemBundle:Default:index.html.twig');
    }

    public function loginAction()
    {
    	return $this->render('BackSystSystemBundle:Login:login.html.twig');
    }

    public function loginSettingsPageAction()
    {
        $em = $this->getDoctrine()->getManager();
        $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');

        $admin = $adminRepo->find(1);

        if($admin != null){
            return $this->render('BackSystSystemBundle:Login:settings.html.twig', array('login'=>$admin->login));
        }else{
            die('Error: Admin account could not be found');
        }
        
    }

    public function loginSettingsUpdateAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');

        $bcrypt = $this->get('bcrypt');
        $session = $this->get('session_handler');

        $login = $request->request->get('login');
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirm = $request->request->get('confirm');

        $admin = $adminRepo->find(1);
        $success = true;
        $success_pass = false;
        $error = "";
        if($admin != null){
            if($bcrypt->verify($currentPassword, $admin->password) === true){
                if($newPassword == $confirm){
                    $admin->login = $login;
                    if(empty($newPassword) === false){
                        $valid = $session->isValidPassword($newPassword);
                        if($valid['success'] === true){
                            $admin->password = $bcrypt->hash($newPassword);
                            $success_pass = true;
                        }else{
                            $success = false;
                            $error = $valid['error'];
                        }
                    }else if(empty($newPassword) === true){
                        // All good -- do nothing here
                    }else{
                        $success = false;

                        $error = "Error: The new password is not valid";
                    }
                }else{
                    $success = false;
                    $error = "Error: Confirmation password does not match with new password";
                }
            }else{
                $success = false;
                $error = "Error: The current password is not correct";
            }
        }else{
            $success = false;
            $error = "Error: The admin account could not be found";
        }

        if($success === true)
        {
            $em->persist($admin);
            $em->flush();
            $session->init($admin);
            return $this->render('BackSystSystemBundle:Login:settings.html.twig', array('login'=>$admin->login, 'success_pass'=>$success_pass));
        }

        return $this->render('BackSystSystemBundle:Login:settings.html.twig', array('login'=>$admin->login, 'error'=>$error));
    }

    public function notificationsPageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
        $admin = $adminRepo->find(1);

        $form = $this->createFormBuilder($admin)
                    ->add('email', 'email', array('required'=>false))
                    ->add('button', 'submit', array('label'=>'Save'))
                    ->getForm();

        $form->handleRequest($request);

        if($form->isValid())
        {
            $em->persist($admin);
            $em->flush();
            return $this->render('BackSystSystemBundle:Notifications:notifications.html.twig', array('form'=>$form->createView(), 'success'=>true, 'email'=>$admin->email));
        }
        return $this->render('BackSystSystemBundle:Notifications:notifications.html.twig', array('form'=>$form->createView(), 'email'=>$admin->email));
    }

    public function testEmailAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
        $admin = $adminRepo->find(1);        
        
        $headers = 'Content-type: text/html; charset=utf-8' . "\r\n";
        $response = array();


        $message = \Swift_Message::newInstance();
        $message->setSubject('BACKUP: Testing notification email')
                ->setFrom('do-not-respond@cronox.'.$request->getHost())
                ->setTo($admin->email)
                ->setBody($this->renderView('BackSystSystemBundle:Default:test_email_notification.html.twig', array()))
                ->setContentType('text/html');

        $success = $this->get('mailer')->send($message);

        return new Response(json_encode(array('success'=>$success)), 200, array('Content-Type'=>'application/json'));
    }

    public function notificationsSuccessAction()
    {
        return $this->render('BackSystSystemBundle:Notifications:notifications.html.twig', array('form'=>$form->createView()));
    }

    public function documentationAction()
    {
        return $this->render('BackSystSystemBundle:Documentation:main.html.twig');
    }

}
















