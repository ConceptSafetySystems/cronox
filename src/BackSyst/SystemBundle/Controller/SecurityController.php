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
use BackSyst\SystemBundle\Entity\SSHKnownHosts;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends Controller implements TokenAuthenticatedController
{

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
        $admin = $adminRepo->find(1);
        if($admin != null){
            $key = $admin->privateKey;
            $pub = $admin->publicKey;

            

            if($key == null) $key = "";
            return $this->render('BackSystSystemBundle:Security:edit_private_key.html.twig', array('key'=>$key, 'pub'=>$pub));
        }
        die('Error: Could not find admin account in Database');
    }

    public function updatePrivateKeyAction(Request $request)
    {
        $sshMan = $this->get('ssh_connect');

        $newKey = $request->request->get('private_key');
        if(!$sshMan->isValidPrivateKey($newKey)){
            $error = "Error: The private key you've entered is not correct.";
            return $this->render('BackSystSystemBundle:Security:edit_private_key.html.twig', array('key'=>$newKey, 'error'=>$error));
        }else{
            $em = $this->getDoctrine()->getManager();
            $adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
            $admin = $adminRepo->find(1);
            if($admin != null){
                $admin->privateKey = $newKey;

                $temp = tmpfile();
                fwrite($temp, $admin->privateKey);
                $tmpMeta = stream_get_meta_data($temp);
                $tmpFilename = $tmpMeta['uri'];
                fseek($temp, 0);
                $pub = exec('ssh-keygen -y -f '.$tmpFilename);
                fclose($temp);

                $admin->publicKey = $pub;
                $em->persist($admin);
                $em->flush();
                return $this->render('BackSystSystemBundle:Security:edit_private_key.html.twig', array('key'=>$newKey, 'pub'=>$pub, 'success'=>true));
            }
            die('Error: Could not find admin account in Database');
        }
    }

    public function didAuthorizeHostAction(Request $request)
    {
        $isAuthorized = $request->request->get('isAuthorized');
        if($isAuthorized == "1"){
            $publicKey = $request->request->get('publicKey');
            $machineId = $request->request->get('machineId');
            $goToRoute = $request->request->get('goToRoute');

            $em = $this->getDoctrine()->getManager();
            $machineRepo = $em->getRepository('BackSystSystemBundle:RemoteMachine');
            $machine = $machineRepo->find($machineId);
            
            $knownHost = new SSHKnownHosts();
            $knownHost->rsaPublicKey = $publicKey;
            $knownHost->hostName = $machine->host;
            
            $em->persist($knownHost);
            $em->flush();
            return $this->redirect($this->generateURL($goToRoute, array('id'=>$machineId)));
        }else{
            $machineId = $request->request->get('machineId');
            $fromRoute = $request->request->get('fromRoute');
            return $this->redirect($this->generateURL($fromRoute, array('id'=>$machineId)));
        }
    }

    public function didAuthorizeHostChangeAction(Request $request)
    {
        $isAuthorized = $request->request->get('isAuthorized');
        if($isAuthorized == "1"){
            $publicKey = $request->request->get('publicKey');
            $machineId = $request->request->get('machineId');
            $goToRoute = $request->request->get('goToRoute');
            
            $em = $this->getDoctrine()->getManager();
            $knownHost = new SSHKnownHosts();
            $knownHost->rsaPublicKey = $publicKey;
            $em->persist($knownHost);
            $em->flush();
            return $this->redirect($this->generateURL($goToRoute, array('id'=>$machineId)));
        }else{
            $machineId = $request->request->get('machineId');
            $fromRoute = $request->request->get('fromRoute');
            return $this->redirect($this->generateURL($fromRoute, array('id'=>$machineId)));
        }
    }

    public function generatePrivateKeyAction(Request $request)
    {
        $config = array(
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
            
        // Create the private and public key
        $res = openssl_pkey_new($config);

        if($res != null && $res != -1 && $res !== false)
        {
            // Extract the private key from $res to $privKey
            openssl_pkey_export($res, $privKey);
                    
            // Extract the public key from $res to $pubKey
            $pubKey = openssl_pkey_get_details($res);
            $pubKey = $pubKey["key"];
            
            $response = json_encode(array('private' => $privKey, 'public' => $pubKey));

            return new Response($response, 200, array('Content-Type'=>'application/json'));
        }
        else
        {
            $response = json_encode(array('error' => "OpenSSL failed to generate the keys. Please check your logs and system configs (eg: openssl.cnf)."));
            return new Response($response, 200, array('Content-Type'=>'application/json'));
        }
        
    }
}
