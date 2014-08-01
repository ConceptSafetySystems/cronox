<?php
namespace BackSyst\SystemBundle\EventListener;

use BackSyst\SystemBundle\Controller\TokenAuthenticatedController;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;

class TokenListener extends Controller
{
    protected $em;
    protected $adminRepo;
    protected $container;

    public function __construct($em, $container)
    {
        $this->container = $container;
        $this->em = $em;
        $this->adminRepo = $em->getRepository('BackSystSystemBundle:Admin');
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $success = false;
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }
        if ($controller[0] instanceof TokenAuthenticatedController) {
            $session = $this->container->get('session_handler');

            $admin = $session->isAdminInitialised();
            $loggedIn = $session->isLoggedIn();
            if(!$admin){
                $request = new Request();
                $request->attributes->set('_controller', 'BackSyst\SystemBundle\Controller\AuthController::initAction');
                $parser = new ControllerNameParser($this->container->get('kernel'));
                $resolver = new ControllerResolver($this->container, $parser);
                $event->setController($resolver->getController($request)); 
            }else if(!$loggedIn){
                $request = new Request();
                $request->attributes->set('_controller', 'BackSyst\SystemBundle\Controller\DefaultController::loginAction');
                $parser = new ControllerNameParser($this->container->get('kernel'));
                $resolver = new ControllerResolver($this->container, $parser);
                $event->setController($resolver->getController($request)); 
            }else{
                return;
            }
            return;
        }
        return;
    }
}
