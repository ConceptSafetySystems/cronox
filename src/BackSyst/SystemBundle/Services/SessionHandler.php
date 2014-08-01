<?php

/**
 *  Author: Nicolas Dijoux
 *  Year: 2014
 *  Organisation: Locatrix Communication
 *  Copyright: Locatrix Communication - All rights reserved
 */

namespace BackSyst\SystemBundle\Services;
use Doctrine\ORM\EntityManager;
use BackSyst\SystemBundle\Entity\Admin;
use BackSyst\SystemBundle\Entity\Report;
use Doctrine\Common\Collections\ArrayCollection;

class SessionHandler
{
	protected $em;
    protected $session;
    protected $container;
    protected $adminRepo;

    /**
     *  The constructor
     *  Remark: Arguments are set in service's declaration in HRPortal\SystemBundle\Resources\config\services.yml
     *  @param (EntityManager) $em, is a Symfony2 Entity Manager
     *  @param $session, is the global session array
     */
    public function __construct(EntityManager $em, $session, $container)
    {
        $this->em = $em;
        $this->session = $session;
        $this->adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
        $this->container = $container;
    }

    /**
     */
    public function init($admin)
    {
        $id = $admin->id;

        $token = md5(uniqid(rand(), true));
        $admin->token = $token;
        $this->em->persist($admin);
        $this->em->flush();

        $this->session->set('token', $token);
        $this->session->set('admin', $admin->login);
        $this->container->get('logger')->info("Session with admin created");
    }

    /**
     *  Checks if admin has been set in database
     *
     */
    public function isAdminInitialised()
    { 
        $qb = $this->adminRepo->createQueryBuilder('a')
                ->select('a.id')
                ->setMaxResults(1)
                ->getQuery();
        $admin = $qb->getResult();

        if(!empty($admin)){
            return true;
        }else{
            return false;
        }    
    }

    /**
     *  Checks wether or not there is a current active session
     *  @return boolean, true if session is active & valid, false otherwise
     */
    public function isLoggedIn()
    {
        if($this->session->has('admin') && $this->session->has('token')){
            $admin = $this->session->get('admin');
            $token = $this->session->get('token');

            $admin = $this->adminRepo->findOneBy(array('login'=>$admin, 'token'=>$token));

            if(!empty($admin)){
                return true;
            }else{
                return false;
            }
        }
        return false;      
    }

    public function get($parameter)
    {
        return $this->session->get($parameter);
    }

    /**
     *  Adds unarbitrary parameters to the session.
     *  @param (array) $sess_info, is the array of parameters and values to add ex: array('user_age'=>32, 'user_city'=>Brisbane)
     */
    public function set($param, $value)
    {
        $this->session->set($param, $value);
    }

    /**
     *  Deletes specific parameters from the current session.
     *  @param (array) $sess_info, is the array of parameters and values to remove. ex: array('user_age', 'user_city')
     */
    public function remove(array $sess_info)
    {
        foreach ($sess_info as $parameter) {
            $this->session->remove($parameter);
        }
    }

    /**
     *  Unsets all parameters and values from the current session. In other words, closes the session
     */
    public function killSession()
    {
        $this->session->invalidate();
    }

    public function isValidPassword($pass)
    {
        $success = true;
        $message = "";
        if(empty($pass)){
            $success = false;
            $message = "Password cannot be empty";
        }else if(strlen($pass) < 6){
            $success = false;
            $message = "Password must be at least 6 characters long";
        }
        return array('success'=>$success, 'error'=>$message);
    }

    public function resetAdmin()
    {
        $admins = $this->adminRepo->findAll();
        if($admins != null){
            foreach ($admins as $admin) {
                $this->em->remove($admin);
            }
            $this->em->flush();
        }
    }

    public function sendEmailNotification($email, $report){

        $status = $report->status;
        $endedAt = "Never Ended";
        if ($report->endedAt != null){
            $endedAt = $report->endedAt->format('d/m/y h:i:s');
        }
        $details = array(   'Report ID' => $report->id,
                            'Backup Name' => $report->backupName,
                            'Status' => $report->status,
                            'Host Name' => $report->machineName,
                            'Host URL' => $report->machineHost,
                            'User' => $report->machineUser,
                            'Started At' => $report->startedAt->format('d/m/y h:i:s'),
                            'Ended At' => $endedAt,
                            'Details' => $report->details 
                        );

        $headers = 'Content-type: text/html; charset=utf-8' . "\r\n";

        mail($email, 'BACKUP: '.$status, $this->container->get('templating')->render('BackSystSystemBundle:Reports:email_notification.html.twig', 
                                            array( 'report'=>$details )), $headers);
    }

}
