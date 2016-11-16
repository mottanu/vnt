<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Document\User;
use AppBundle\Document\Token;
use AppBundle\Document\RecoverCode;
use AppBundle\Document\InvitationCode;
use AppBundle\Controller\UtilsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use AppBundle\Entity\UserSignup;
use AppBundle\Entity\UserLogin;
use Symfony\Component\Form\FormError;
use AppBundle\Entity\InregistrareForm;
use AppBundle\Entity\EmptyForm;
use AppBundle\Entity\InvitatieForm;
use AppBundle\Document\Raport;

class AgentFondVanatoareController extends Controller
{
  
  /**
   * Matches /cms/af/dashboard exactly
   *
   * @Route("/cms/af/dashboard", name="afDashboard")
   * @Method({"GET"})
   *
   */
  public function afDashboard(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user || !$user->isAgentFondVanatoare) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('dashboard')
    );
    return $this->render('cms/af/dashboard.html.twig', $params);
  }
  
}