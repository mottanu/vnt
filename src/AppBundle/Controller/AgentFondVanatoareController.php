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
   * @Method({"GET"})
   *
   */
  public function cmsAfAutorizatii(Request $request) {
    
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
    
    $userRepository = $dm->getRepository('AppBundle:User');
    $speciesRepository = $dm->getRepository('AppBundle:Species');
    
    $tipTradus = array(
      '0' => "Individuala",
      '1' => "Grup restrans",
      '2' => "Colectiva"
    );
    $autorizatiiCursor = $dm->createQueryBuilder('AppBundle:Autorizatie')
      ->sort('created', 'desc')
      ->getQuery()->execute();
    $listaAutorizatii = array();
    foreach($autorizatiiCursor as $autorizatie) {
      $autorizatieDisplay = $autorizatie->display();
      $organizator = $userRepository->findOneById($autorizatie->organizatorId);
      $autorizatieDisplay['organizator'] = $organizator->display();
      $autorizatieDisplay['tipul'] = $tipTradus[$autorizatie->tip]; 
      $autorizatieDisplay['activa'] = $autorizatie->activa ? "DA" : "NU"; 
      $listaAutorizatii[] = $autorizatieDisplay;
    }
  
    $params = array(
      'user' => $user,
      'navigation' => UtilsController::getNavigation('autorizatii'),
      'listaAutorizatii' => $listaAutorizatii
    );
    return $this->render('cms/af/autorizatii.html.twig', $params);
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