<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\UtilsController;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
      UtilsController::initData();
      
      var_dump($request->getSession()->get('user'));
      
      // replace this example code with whatever you need
      return $this->render('default/index.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      ]);
    }
    
    /**
     * @Route("/test", name="test")
     */
    public function test(Request $request)
    {
      // UtilsController::initData();
      
      // replace this example code with whatever you need
      return $this->render('cms.html.twig', [
        'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      ]);
    }
}
