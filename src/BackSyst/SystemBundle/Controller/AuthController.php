<?php

/**
 *  Author: Nicolas Dijoux
 *  Year: 2014
 *  Organisation: Locatrix Communication
 *  Copyright: Locatrix Communication - All rights reserved
 *
 */

namespace BackSyst\SystemBundle\Controller;

use BackSyst\SystemBundle\Entity\Admin;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sinner\Phpseclib\Net\Net_SSH2 as NetSsh2;
use Sinner\Phpseclib\Crypt\Crypt_RSA as CryptRsa;

class AuthController extends Controller
{
    public function indexAction()
    {
        return $this->render('BackSystSystemBundle:Default:index.html.twig');
    }

    public function initAction()
    {
        return $this->render('BackSystSystemBundle:Login:init.html.twig');
    }

    public function authAction(Request $request)
    {
        $bcrypt = $this->get('bcrypt');
        $session = $this->get('session_handler');

        if(!$session->isLoggedIn()){
            $em = $this->getDoctrine()->getManager();
            $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');

            $login = $request->request->get('login');
            $login = strtolower($login);
            $password = $request->request->get('password');

            $admin = $adminRepo->findOneBy(array('login'=>$login));

            if($admin != null){
                if($bcrypt->verify($password, $admin->password) === true){
                    $session->init($admin);
                }else{
                    return $this->render('BackSystSystemBundle:Login:login.html.twig', array('error'=>true));
                }
            }else{
                return $this->render('BackSystSystemBundle:Login:login.html.twig', array('error'=>true));
            }

            return $this->redirect($this->generateUrl('home'));
        }

        return $this->redirect($this->generateUrl('home'));
    }

    public function logoutAction()
    {
        $session = $this->get('session_handler');
        $session->killSession();
        return $this->redirect($this->generateUrl("home"));
    }

    public function saveAdminCredentialsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');

        $bcrypt = $this->get('bcrypt');
        $session = $this->get('session_handler');

        $login = $request->request->get('login');
        $password = $request->request->get('password');
        $confirm = $request->request->get('confirm');
        $errors = array();
        if(!empty($login) && $password == $confirm){
            $valid = $session->isValidPassword($password);
            if($valid['success'] === true){
                $admin = new Admin();
                $admin->id = 1;
                $admin->password = $bcrypt->hash($password);
                $admin->login = strtolower($login);
                $em->persist($admin);
                $em->flush();
                $session->init($admin);
                return $this->redirect($this->generateUrl("home"));
            }else{
                $errors['password'] = $valid['error'];
            }
        }else{
            if(empty($login)){
                $errors['login'] = true;
            }
            if($password != $confirm){
                $errors['confirm'] = true;
            }
        }
        return $this->render('BackSystSystemBundle:Login:init.html.twig', array('error'=>$errors));
    }

    public function forgotAction()
    {
        return $this->render('BackSystSystemBundle:Login:forgot.html.twig');
    }

}
