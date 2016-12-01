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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Entity\UserSignup;
use AppBundle\Entity\UserLogin;
use Symfony\Component\Form\FormError;
use AppBundle\Entity\InregistrareForm;
use AppBundle\Entity\EmptyForm;
use AppBundle\Entity\CotaForm;
use AppBundle\Document\Cota;

class AgentMinisterController extends Controller
{
  
  /**
   * Matches /cms/am/dashboard exactly
   *
   * @Route("/cms/am/dashboard", name="amDashboard")
   * @Method({"GET"})
   *
   */
  public function amDashboard(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user || !$user->isAgentMinister) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('dashboard')
    );
    return $this->render('cms/am/dashboard.html.twig', $params);
  }
  
  /**
   * Matches /cms/am/cote exactly
   *
   * @Route("/cms/am/cote", name="cmsAmCote")
   * @Method({"GET","POST"})
   *
   */
  public function cmsAmCote(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentMinister && !$user->isAdmin) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $userRepository = $dm->getRepository('AppBundle:User');
    $speciesRepository = $dm->getRepository('AppBundle:Species');
    
    $speciesCursor = $dm->createQueryBuilder('AppBundle:Species')
      ->sort('name', 'asc')
      ->getQuery()->execute();
    $listaSpecii = array();
    $listaSpeciiSelect = array();
    foreach($speciesCursor as $specie) {
      $listaSpecii[] = $specie->display();
      $listaSpeciiSelect[$specie->name] = $specie->getId();
    }
    
    $agentiFondVanatoareCursor = $dm->createQueryBuilder('AppBundle:User')
      ->field('isAgentFondVanatoare')->equals(true)
      ->sort('name', 'asc')
      ->getQuery()->execute();
    $listaAgentiFondVanatoare = array();
    $listaAgentiVanatoareSelect = array();
    foreach($agentiFondVanatoareCursor as $agent) {
      $listaAgentiFondVanatoare[] = $agent->display();
      $listaAgentiVanatoareSelect[$agent->firstname . " " . $agent->lastname] = $agent->getId();
    }
    
    $cotaForm = new CotaForm();
    $form = $this->createFormBuilder($cotaForm)
        ->add('userId', ChoiceType::class, array(
          'required' => true,
          'choices'  => $listaAgentiVanatoareSelect,
          'label' => 'Agent Fond Vanatoare'))
        ->add('speciesId', ChoiceType::class, array(
          'required' => true,
          'choices'  => $listaSpeciiSelect,
          'label' => 'Specie'))
        ->add('nr', IntegerType::class, array(
          'data' => 0,
          'label' => 'Cota aprobata'))
        ->add('salveaza', SubmitType::class, array('label' => 'Trimite invitatie'))
        ->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      if ($form->get('salveaza')->isClicked()) {
        $cotaForm = $form->getData();
        
        $newCota = new Cota();
        $newCota->userId = $cotaForm->userId;
        $newCota->speciesId = $cotaForm->speciesId;
        $newCota->nr = $cotaForm->nr;
        $newCota->userAprobareId = $user->getId();
        $newCota->created = date("c");
        $dm->persist($newCota);
        $dm->flush();
      }
    }
    
    $cursorCote = $dm->createQueryBuilder('AppBundle:Cota')
      ->sort('created', 'desc')
      ->getQuery()->execute();
    
    $listaCote = array();
    foreach($cursorCote as $cota) {
      $cotaDisplay = $cota->display();
      $agentFondVanatoare = $userRepository->findOneById($cota->userId);
      $userAprobare = $userRepository->findOneById($cota->userAprobareId);
      $specie = $speciesRepository->findOneById($cota->speciesId);
      $cotaDisplay['user'] = $agentFondVanatoare->display();
      $cotaDisplay['specie'] = $specie->display();
      $cotaDisplay['userAprobare'] = $userAprobare->display();
      $listaCote[] = $cotaDisplay;
    }

    $params = array(
      'user' => $user,
      'navigation' => UtilsController::getNavigation('cote'),
      'listaCote' => $listaCote,
      'listaSpecii' => $listaSpecii,
      'listaAgentiFondVanatoare' => $listaAgentiFondVanatoare,
      'form' => $form->createView()
    );
    return $this->render('cms/am/cote.html.twig', $params);
  }
  
  /**
   * Matches /cms/am/cote/{cotaId} exactly
   *
   * @Route("/cms/am/cote/{cotaId}", name="cmsAmCoteDetalii")
   * @Method({"GET","POST"})
   *
   */
  public function cmsAmCoteDetalii(Request $request, $cotaId = null) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentMinister && !$user->isAdmin) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    // TO DO: Editare cota aprobata.
    
    $params = array(
      'user' => $user,
      'navigation' => UtilsController::getNavigation('cote'),
    );
    return $this->render('cms/am/cotedetalii.html.twig', $params);
  }
  
}