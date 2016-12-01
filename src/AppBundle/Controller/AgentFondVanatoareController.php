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
use AppBundle\Document\Autorizatie;
use AppBundle\Controller\UtilsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use AppBundle\Entity\UserSignup;
use AppBundle\Entity\UserLogin;
use Symfony\Component\Form\FormError;
use AppBundle\Entity\InregistrareForm;
use AppBundle\Entity\EmptyForm;
use AppBundle\Entity\AutorizatieForm;
use AppBundle\Entity\CotaForm;
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
  
  /**
   * Matches /cms/af/autorizatii exactly
   *
   * @Route("/cms/af/autorizatii", name="cmsAfAutorizatii")
   * @Method({"GET","POST"})
   *
   */
  public function cmsAfAutorizatii(Request $request) {
    
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
   * Matches /cms/af/autorizatii/adauga exactly
   *
   * @Route("/cms/af/autorizatii/adauga", name="cmsAfAutorizatiiAdauga")
   * @Method({"GET","POST"})
   *
   */
  public function cmsAfAutorizatiiAdauga(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentFondVanatoare && !$user->isAdmin) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }

    $listaTipuri = UtilsController::getTipuriAutorizatie();
    
    $agentiColectoriCursor = $dm->createQueryBuilder('AppBundle:User')
      ->field('fondVanatoare')->equals($user->fondVanatoare)
      ->sort('firstname', 'asc')
      ->getQuery()->execute();
    $listaOrganizatori= array();
    foreach($agentiColectoriCursor as $agent) {
      $listaOrganizatori[$agent->firstname . " " . $agent->lastname] = $agent->getId();
    }
    
    $autorizatieForm = new AutorizatieForm();
    $form = $this->createFormBuilder($autorizatieForm)
        ->add('tip', ChoiceType::class, array(
          'required' => true,
          'choices'  => $listaTipuri,
          'label' => 'Tip autorizatie'))
        ->add('organizatorId', ChoiceType::class, array(
          'required' => true,
          'choices'  => $listaOrganizatori,
          'label' => 'Tip autorizatie'))
        ->add('dataInceput', DateType::class, array(
          'widget' => 'choice',
          'label' => 'Data Inceput'))
        ->add('dataSfarsit', DateType::class, array(
          'widget' => 'choice',
          'label' => 'Data Sfarsit'))
        ->add('salveaza', SubmitType::class, array('label' => 'Salveaza autorizatie'))
        ->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      if ($form->get('salveaza')->isClicked()) {
        $autorizatieForm = $form->getData();
        
        $contor = UtilsController::getUrmatorulNumarAutorizatie($dm);
        $numarAutorizatie = UtilsController::getSerieAgentFondVanatoare($user->serieAgentFondVanatoare) . "17" . UtilsController::getContor($contor) . $autorizatieForm->tip;
        
        $autorizatieNoua = new Autorizatie();
        $autorizatieNoua->numar = $numarAutorizatie;
        $autorizatieNoua->tip = $autorizatieForm->tip;
        $autorizatieNoua->userId = $user->getId();
        $autorizatieNoua->organizatorId = $autorizatieForm->organizatorId;
        $autorizatieNoua->cote = array();
        $autorizatieNoua->vanatori = array();
        $autorizatieNoua->dataInceput = $autorizatieForm->dataInceput;
        $autorizatieNoua->dataSfarsit = $autorizatieForm->dataSfarsit;
        $autorizatieNoua->contor = $contor;
        $autorizatieNoua->an = 2017;
        $autorizatieNoua->created = date("c");
        $dm->persist($autorizatieNoua);
        $dm->flush();
        
        return $this->redirectToRoute('cmsAfAutorizatiiModifica', array('autorizatieId' => $autorizatieNoua->getId()));
      }
    }

    $params = array(
      'user' => $user,
      'navigation' => UtilsController::getNavigation('autorizatii'),
      // 'listaCote' => $listaCote,
      // 'listaSpecii' => $listaSpecii,
      // 'listaAgentiFondVanatoare' => $listaAgentiFondVanatoare,
      'form' => $form->createView()
    );
    return $this->render('cms/af/autorizatieadauga.html.twig', $params);
  }
  
  /**
   * Matches /cms/af/autorizatii/{autorizatieId} exactly
   *
   * @Route("/cms/af/autorizatii/{autorizatieId}", name="cmsAfAutorizatiiModifica")
   * @Method({"GET","POST"})
   *
   */
  public function cmsAfAutorizatiiModifica(Request $request, $autorizatieId = null) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentFondVanatoare && !$user->isAdmin) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $autorizatiiRepository = $dm->getRepository('AppBundle:Autorizatie');
    $speciesRepository = $dm->getRepository('AppBundle:Species');
    $coteRepository = $dm->getRepository('AppBundle:Cota');
    
    $autorizatie = $autorizatiiRepository->findOneById($autorizatieId);
    if(!$autorizatie)
      return $this->redirectToRoute('cmsAfAutorizatii');
    
    $coteCursor = $dm->createQueryBuilder('AppBundle:Cota')
      ->field('userId')->equals($user->getId())
      ->getQuery()->execute();
    $listaCote = array();
    $listeCoteSelect = array();
    foreach($coteCursor as $cota) {
      $cotaDisplay = $cota->display();
      $specie = $speciesRepository->findOneById($cota->speciesId);
      $cotaDisplay['specie'] = $specie->display();
      $listeCote[] = $cotaDisplay;
      $listeCoteSelect[$specie->name] = $specie->getId();
    }
    
    $cotaForm = new CotaForm();
    $form = $this->createFormBuilder($cotaForm)
        ->add('speciesId', ChoiceType::class, array(
          'required' => true,
          'choices'  => $listeCoteSelect,
          'label' => 'Specie'))
        ->add('nr', IntegerType::class, array(
          'required' => true,
          'label' => 'Numar'))
        ->add('adauga', SubmitType::class, array('label' => 'Adauga'))
        ->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      if ($form->get('adauga')->isClicked()) {
        $cotaForm = $form->getData();
        
        $autorizatie->cote[$cotaForm->speciesId] = $cotaForm->nr;
        
        $dm->flush();
      }
    }

    $params = array(
      'user' => $user,
      'navigation' => UtilsController::getNavigation('autorizatieadauga'),
      'autorizatie' => $autorizatie,
      'form' => $form->createView()
    );
    return $this->render('cms/af/autorizatieedit.html.twig', $params);
  }
  
}