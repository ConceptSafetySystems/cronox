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

class ReportsController extends Controller implements TokenAuthenticatedController
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $reportRepo = $em->getRepository('BackSystSystemBundle:Report');
        //$reportsEnt = $reportRepo->findAll();

        $reportsQuery = $reportRepo->createQueryBuilder('r')
            ->groupBy('r.backupOriginalId')
            ->getQuery();
        $reportsEnt = $reportsQuery->getResult();

        $reports = array();
        if($reportsEnt != null){
            foreach (array_reverse($reportsEnt) as $r) {
                $status = "";
                if($r->isSuccess === false ){
                    $status = 'ERROR';
                }else{
                    $status = $r->status;
                }
                $ended = $r->endedAt;
                $diff = "N/A";
                if($ended == null){
                    $ended = "Never ended";
                }else{
                    $ended = $ended->format('d/m/Y h:i:s');
                    $diff = $this->duration($r->endedAt, $r->startedAt);
                }

                $reports[] = array( 'id'=>$r->id,
                                    'server'=>$r->machineName,
                                    'backupName'=>$r->backupName, 
                                    'start'=>$r->startedAt->format('d/m/Y h:i:s'),
                                    'finish'=>$ended,
                                    'duration'=>$diff,
                                    'size'=>$this->prettySize($r->size),
                                    'status'=>$r->status,
                                    'machineId'=>$r->machineOriginalId);
            }
        }
        return $this->render('BackSystSystemBundle:Reports:reports.html.twig', array('reports'=>$reports));
    }

    public function historyAction($id)
    {
        if($id != "0" && $id != null){
            $em = $this->getDoctrine()->getManager();
            $reportRepo = $em->getRepository('BackSystSystemBundle:Report');
            $reportsEnt = $reportRepo->findBy(array('machineOriginalId'=>$id));

            $server = "";
            $reports = array();

            if($reportsEnt != null){
                foreach (array_reverse($reportsEnt) as $r) {
                    $server = $r->machineName;
                    $status = "";
                    if($r->isSuccess === false ){
                        $status = 'ERROR';
                    }else{
                        $status = $r->status;
                    }
                    $ended = $r->endedAt;
                    $diff = "N/A";
                    if($ended == null){
                        $ended = "Never ended";
                    }else{
                        $ended = $ended->format('d/m/Y h:i:s');
                        $diff = $this->duration($r->endedAt, $r->startedAt);
                    }

                    $reports[] = array( 'id'=>$r->id,
                                        'server'=>$r->machineName, 
                                        'start'=>$r->startedAt->format('d/m/Y h:i:s'),
                                        'finish'=>$ended,
                                        'duration'=>$diff,
                                        'size'=>$this->prettySize($r->size),
                                        'status'=>$r->status);
                }
            }
            
            return $this->render('BackSystSystemBundle:Reports:history.html.twig', array('reports'=>$reports, 'serverName'=>$server));
        }
        
        return $this->redirect($this->generateUrl('list_reports'));
    }

    public function detailsPageAction($id)
    {
        if($id == null || $id == 0){
            die('Error: Report does not exist');
        }
        
        $em = $this->getDoctrine()->getManager();
        $reportRepo = $em->getRepository('BackSystSystemBundle:Report');
        $r = $reportRepo->find($id);

        if($r != null){

            $ended = $r->endedAt;
            $diff = "N/A";
            if($ended == null){
                $ended = "Never ended";
            }else{
                $ended = $ended->format('d/m/Y h:i:s');
                $diff = $this->duration($r->endedAt, $r->startedAt);
            }

            $output = $r->output;
            $readMore = false;
            if(strlen($output) > 100)
            {
                $readMore = true;
                $output = substr($output, 0, 100) ." ...";
            }
            $report = array('Report Id'=>$r->id,
                            'Backup Name'=>$r->backupName,
                            'Started At'=>$r->startedAt->format('d/m/Y h:i:s'),
                            'Finished At'=>$ended,
                            'Duration'=>$diff,
                            'Size'=>$this->prettySize($r->size),
                            'Status'=>$r->status,
                            'Server'=>$r->machineName,
                            'URL'=>$r->machineHost,
                            'User'=>$r->machineUser,
                            'Arguments'=>$r->commandLine,
                            'Details'=>$r->details,
                            'Path of Temporary Script file'=>$r->pathScriptFile,
                            'Destination of Backup'=>$r->destination,
                            'Script'=>str_ireplace("\n", "<br/>", $r->script),
                            'Output'=>str_ireplace("\n", "<br/>",$output));
            return $this->render('BackSystSystemBundle:Reports:details.html.twig', array('report'=>$report, 'readMore'=>$readMore));
        }

        return $this->render('BackSystSystemBundle:Reports:reports.html.twig');
    }

    public function readFullOutputAction($id = 0)
    {
        if($id == null || $id == 0)
        {
            die('Error: Report does not exist');
        }

        $em = $this->getDoctrine()->getManager();
        $reportRepo = $em->getRepository('BackSystSystemBundle:Report');
        $r = $reportRepo->find($id);

        if($r != null){
            return $this->render('BackSystSystemBundle:Reports:full_report.html.twig', array('output'=>$r->output, 'id'=>$r->id));
        }else{
            die('Error: Report could not be found');
        }
    }

    public function deleteAction($id = 0)
    {
        if($id == null || $id == 0){
            die('Error: Report does not exist');
        }

        $em = $this->getDoctrine()->getManager();
        $reportRepo = $em->getRepository('BackSystSystemBundle:Report');
        $r = $reportRepo->find($id);

        if($r != null){
            $em->remove($r);
            $em->flush();
            return $this->redirect($this->generateUrl('list_reports'));
        }else{
            die('Error: Report could not be found');
        }
    }

    public function duration($time1, $time2)
    {
        $diffSigned = $time1->format('U') - $time2->format('U');
        $diff = abs($diffSigned);
        $h = floor($diff/3600);
        if($h<10) $h = "0$h";
        $m = ($diff/60)%60;
        if($m<10) $m = "0$m";
        $s = $diff%60;
        if($s<10) $s = "0$s";

        $diff = "$h:$m:$s";
        if($diffSigned < 0)
            $diff = "- $diff";
        return $diff;
    }

    public function prettySize($bytes){
        if($bytes < 1024){
            return "$bytes bytes";
        }else if($bytes >= 1024 && $bytes <= 1048576){
            $kb = $bytes / 1024;
            $kb = number_format($kb, 2);
            return "$kb kb";
        }else if($bytes >= 1048576 && $bytes <= 1073741824){
            $mb = $bytes / 1048576;
            $mb = number_format($mb, 2);
            return "$mb mb";
        }else{
            $gb = $bytes / 1073741824;
            $gb = number_format($gb, 2);
            return "$gb gb";
        }
    }

}
















