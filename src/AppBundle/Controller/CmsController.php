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

class CmsController extends Controller
{
  
  /**
   * Matches /cms/dashboard exactly
   *
   * @Route("/cms/dashboard", name="cmsDashboard")
   * @Method({"GET"})
   *
   */
  public function cmsDashboard(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('dashboard')
    );
    return $this->render('cms/dashboard.html.twig', $params);
  }
  
  /**
   * Matches /cms/inregistrari exactly
   *
   * @Route("/cms/inregistrari", name="cmsInregistrari")
   * @Method({"GET"})
   *
   */
  public function cmsInregistrari(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $inregistrariRepository = $dm->getRepository('AppBundle:Inregistrare');
    $speciiRepository = $dm->getRepository('AppBundle:Species');
    $fondVanatoareRepository = $dm->getRepository('AppBundle:FondVanatoare');
    
    $startTime = 0;
    $finish = new \DateTime(); 
    $finish->setTimestamp(time()); 
    $cursorRapoarte = $dm->createQueryBuilder('AppBundle:Raport')
      ->field('userId')->equals($user->getId())
      ->sort('created', 'desc')
      ->getQuery()->execute();
    if(count($cursorRapoarte) > 0) {
      $startTime = $cursorRapoarte->getNext()->created->getTimestamp();
    }
    
    $start = new \DateTime(); 
    $start->setTimestamp($startTime);
    $inregistrariCursor = $dm->createQueryBuilder('AppBundle:Inregistrare')
      ->field('userId')->equals($user->getId())
      ->field('created')->gt($start)
      ->field('created')->lte($finish)
      ->sort('created', 'desc')
      ->getQuery();
    
    $listaInregistrari = array();
    $nr = 0;
    foreach($inregistrariCursor as $inregistrare) {
      $nr++;
      $fondVanatoare = $fondVanatoareRepository->findOneById($inregistrare->fondVanatoare);
      $specie = UtilsController::getSpecie($inregistrare->speciesId, $dm);

      $listaInregistrari[] = array(
        'id' => $inregistrare->getId(),
        'nr' => $nr,
        'observatii' => $inregistrare->observatii,
        'specie' => $specie != null ? $specie->name : "",
        'numar' => $inregistrare->nr,
        'fondVanatoare' => $fondVanatoare != null ? $fondVanatoare->name : "",
        'data' => is_string($inregistrare->created) ? $inregistrare->created : $inregistrare->created->format('Y-m-j H:i:s')
      );
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('inregistrari'),
      'inregistrari' => $listaInregistrari
    );
    return $this->render('cms/inregistrari.html.twig', $params);
  }
  
  /**
   * Matches /cms/inregistrari/{inregistrareId}/edit exactly
   *
   * @Route("/cms/inregistrari/{inregistrareId}/edit", name="inregistrareEdit")
   * @Method({"GET", "POST"})
   *
   */
  public function inregistrareEdit(Request $request, $inregistrareId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $inregistrareRepository = $dm->getRepository('AppBundle:Inregistrare');
    $inregistrare = $inregistrareRepository->findOneById($inregistrareId);
    if (!$inregistrare) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if($inregistrare->userId != $user->getId()) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }

    $inregistrareForm = new InregistrareForm();

    $form = $this->createFormBuilder($inregistrareForm)
        ->add('nr', IntegerType::class, array(
          'required' => true,
          'label'  => 'Numar',
          'data' => $inregistrare->nr))
        ->add('observatii', TextType::class, array(
          'label'  => 'Observatii',
          'data' => $inregistrare->observatii))
        ->add('inregistrareId', HiddenType::class, array(
          'data' => $inregistrareId))
        ->add('salveaza', SubmitType::class, array('label' => 'Salveaza'))
        ->add('sterge', SubmitType::class, array('label' => 'Sterge'))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      
      if ($form->get('salveaza')->isClicked()) {
        $inregistrareForm = $form->getData();
      
        $inregistrare->nr = $inregistrareForm->nr;
        $inregistrare->observatii = $inregistrareForm->observatii;
        $dm->flush();
      }
      
      if ($form->get('sterge')->isClicked()) {
        $dm->remove($inregistrare);
        $dm->flush();
        return $this->redirectToRoute('cmsInregistrari');
      }
      
    }

    return $this->render('cms/inregistrare.html.twig', array(
      'form' => $form->createView(),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('inregistrari'),
      'nr' => $inregistrare->nr
    ));
  }
  
  /**
   * Matches /cms/inregistrari/{inregistrareId}/detalii exactly
   *
   * @Route("/cms/inregistrari/{inregistrareId}/detalii", name="inregistrareDetalii")
   * @Method({"GET"})
   *
   */
  public function inregistrareDetalii(Request $request, $inregistrareId = null) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $inregistrareRepository = $dm->getRepository('AppBundle:Inregistrare');
    $inregistrare = $inregistrareRepository->findOneById($inregistrareId);
    if (!$inregistrare) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }

    return $this->render('cms/inregistraredetalii.html.twig', array(
      'user' => $user,
      'navigation' => UtilsController::getNavigation('inregistrari'),
      'inregistrare' => $inregistrare->displayWeb()
    ));
  }
  
  /**
   * Matches /cms/raport/{raportId} exactly
   *
   * @Route("/cms/raport/{raportId}", name="cmsRaport")
   * @Method({"GET", "POST"})
   *
   */
  public function cmsRaport(Request $request, $raportId = null) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $emptyForm = new EmptyForm();

    $form = $this->createFormBuilder($emptyForm)
        ->add('generare', SubmitType::class, array('label' => 'Generare raport'))
        ->getForm();
    $form->handleRequest($request);

    $butonGenerare = true;
    $startTime = 0;
    $finish = new \DateTime(); 
    $finish->setTimestamp(time()); 
    $butonNavigare = "raport";
    if($raportId == "curent") {
      $cursorRapoarte = $dm->createQueryBuilder('AppBundle:Raport')
        ->field('userId')->equals($user->getId())
        ->sort('created', 'desc')
        ->getQuery()->execute();
      if(count($cursorRapoarte) > 0) {
        $startTime = $cursorRapoarte->getNext()->created->getTimestamp();
      }
      
      $start = new \DateTime(); 
      $start->setTimestamp($startTime);
      $inregistrariCursor = $dm->createQueryBuilder('AppBundle:Inregistrare')
        ->field('userId')->equals($user->getId())
        ->field('created')->gt($start)
        ->field('created')->lte($finish)
        ->sort('created', 'desc')
        ->getQuery();
    
      $speciesList = UtilsController::getSpeciesList($dm);
      foreach($inregistrariCursor as $inregistrare) {
        $speciesList[$inregistrare->speciesId]['total'] += $inregistrare->nr;
      }
    }
    else {
      $butonNavigare = "istoric";
      $butonGenerare = false;
      $raportRepository = $dm->getRepository('AppBundle:Raport');
      $raport = $raportRepository->findOneById($raportId);
      if (!$raport) {
        return $this->redirectToRoute('cmsIstoric');
      }
      $speciesList = $raport->total;
    }
    
    if ($form->isSubmitted() && $form->isValid()) {
      
      if ($form->get('generare')->isClicked()) {
        $raportNou = new Raport();
        $raportNou->userId = $user->getId();
        $raportNou->fondVanatoare = $user->fondVanatoare;
        $raportNou->unitateJudeteana = $user->unitateJudeteana;
        $raportNou->created = $raportNou->updated = date("c");
        $raportNou->total = $speciesList;
        $dm->persist($raportNou);
        $dm->flush();
        return $this->redirectToRoute('cmsIstoric');
      }
    }

    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation($butonNavigare),
      'listaSpecii' => $speciesList,
      'generare' => $butonGenerare,
      'form' => $form->createView()
    );
    return $this->render('cms/raport.html.twig', $params);
  }
  
  /**
   * Matches /cms/istoric exactly
   *
   * @Route("/cms/istoric", name="cmsIstoric")
   * @Method({"GET"})
   *
   */
  public function cmsIstoric(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }

    $fondVanatoareRepository = $dm->getRepository('AppBundle:FondVanatoare');

    $cursorRapoarte = $dm->createQueryBuilder('AppBundle:Raport')
      ->field('userId')->equals($user->getId())
      ->sort('created', 'desc')
      ->getQuery()->execute();
    
    $listaRapoarte = array();
    $nr = 0;
    foreach($cursorRapoarte as $raport) {
      $nr++;
      $fondVanatoare = $fondVanatoareRepository->findOneById($raport->fondVanatoare);

      $listaRapoarte[] = array(
        'id' => $raport->getId(),
        'nr' => $nr,
        'fondVanatoare' => $fondVanatoare != null ? $fondVanatoare->name : "",
        'dataGenerare' => is_string($raport->created) ? $raport->created : $raport->created->format('Y-m-j H:i:s')
      );
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('istoric'),
      'rapoarte' => $listaRapoarte
    );
    return $this->render('cms/istoric.html.twig', $params);
  }
  
  /**
   * Matches /cms/fondvanatoare exactly
   *
   * @Route("/cms/fondvanatoare", name="cmsFondVanatoare")
   * @Method({"GET"})
   *
   */
  public function cmsFondVanatoare(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentJudetean && !$user->isAgentMinister) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }

    $unitateJudeteanaRepository = $dm->getRepository('AppBundle:UnitateJudeteana');
    $unitateJudeteana = $unitateJudeteanaRepository->findOneById($user->unitateJudeteana);

    $cursorFonduriVanatoare = $dm->createQueryBuilder('AppBundle:FondVanatoare')
      ->field('unitateJudeteana')->equals($user->unitateJudeteana)
      ->sort('name', 'asc')
      ->getQuery()->execute();
    
    $listaFonduriVanatoare = array();
    foreach($cursorFonduriVanatoare as $fondVanatoare) {
      $listaFonduriVanatoare[] = $fondVanatoare->display();
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('fondVanatoare'),
      'fonduriVanatoare' => $listaFonduriVanatoare,
      'unitateJudeteana' => $unitateJudeteana->name
    );
    return $this->render('cms/fondvanatoare.html.twig', $params);
  }
  
  /**
   * Matches /cms/fondvanatoare/{fondId} exactly
   *
   * @Route("/cms/fondvanatoare/{fondId}", name="cmsFondVanatoareDetalii")
   * @Method({"GET", "POST"})
   *
   */
  public function cmsFondVanatoareDetalii(Request $request, $fondId = null) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentJudetean && !$user->isAgentMinister) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }

    $unitateJudeteanaRepository = $dm->getRepository('AppBundle:UnitateJudeteana');
    $unitateJudeteana = $unitateJudeteanaRepository->findOneById($user->unitateJudeteana);
    
    $fondVanatoareRepository = $dm->getRepository('AppBundle:FondVanatoare');
    $fondVanatoare = $fondVanatoareRepository->findOneById($fondId);
    
    if(!$fondVanatoare) {
      return $this->redirectToRoute('cmsFondVanatoare');
    }
    
    
    $invitatieForm = new InvitatieForm();
    $form = $this->createFormBuilder($invitatieForm)
        ->add('email', EmailType::class, array(
          'required' => true,
          'label'  => 'Email'))
        ->add('unitateJudeteana', HiddenType::class, array(
          'data' => $unitateJudeteana->getId()))
        ->add('fondVanatoare', HiddenType::class, array(
          'data' => $fondVanatoare->getId()))
        ->add('salveaza', SubmitType::class, array('label' => 'Trimite invitatie'))
        ->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      if ($form->get('salveaza')->isClicked()) {
        $invitatieForm = $form->getData();

        $userRepository = $dm->getRepository('AppBundle:User');
        $checkUser = $userRepository->findOneByEmail($invitatieForm->email);
        if ($checkUser)
          $form->get('email')->addError(new FormError('Adresa de email este deja folosita!'));
        else {
          $checkUnitateJudeteana = $unitateJudeteanaRepository->findById($invitatieForm->unitateJudeteana);
          if (!$checkUnitateJudeteana)
            $form->get('email')->addError(new FormError('Lipsa unitate judeteana!'));
          else {
            $checkFondVanatoare = $fondVanatoareRepository->findById($invitatieForm->fondVanatoare);
            if (!$checkFondVanatoare)
              $form->get('email')->addError(new FormError('Lipsa fond vanatoare!'));
            else {
              $invitationCodeRepository = $dm->getRepository('AppBundle:InvitationCode');
              $checkInvitationCode = $invitationCodeRepository->findOneByEmail($invitatieForm->email);
              if ($checkInvitationCode) {
                $checkInvitationCode->unitateJudeteana = $invitatieForm->unitateJudeteana;
                $checkInvitationCode->fondVanatoare = $invitatieForm->fondVanatoare;
                $checkInvitationCode->code = UtilsController::generateInvitationCode();
              }
              else {
                $checkInvitationCode = new InvitationCode();
                $checkInvitationCode->email = $invitatieForm->email;
                $checkInvitationCode->unitateJudeteana = $invitatieForm->unitateJudeteana;
                $checkInvitationCode->fondVanatoare = $invitatieForm->fondVanatoare;
                $checkInvitationCode->code = UtilsController::generateInvitationCode();
                $checkInvitationCode->created = date("Y-m-j H:i:s");
                $dm->persist($checkInvitationCode);
              }
              $dm->flush();
              return $this->redirectToRoute('cmsFondVanatoareDetalii', array('fondId' => $fondId));
            }
          }
        }
      }
    }

    $cursorUseri = $dm->createQueryBuilder('AppBundle:User')
      ->field('fondVanatoare')->equals($fondVanatoare->getId())
      ->sort('created', 'asc')
      ->getQuery()->execute();
    
    $listaUseri = array();
    foreach($cursorUseri as $agentColector) {
      $agentDisplay = $agentColector->display();
      $agentDisplay['statusDisplay'] = $agentColector->active ? "activ" : "suspendat";
      $agentDisplay['dataInregistrare'] = $agentColector->created->format("Y-m-j H:i:s");
      $listaUseri[] = $agentDisplay;
    }
    
    $cursorInvitatii = $dm->createQueryBuilder('AppBundle:InvitationCode')
      ->field('fondVanatoare')->equals($fondVanatoare->getId())
      ->field('active')->equals(true)
      ->sort('created', 'asc')
      ->getQuery()->execute();
    
    $listaInvitatii = array();
    foreach($cursorInvitatii as $invitatie) {
      $invitatieDisplay = $invitatie->display();
      $invitatieDisplay['dataInregistrare'] = is_string($invitatie->created) ? $invitatie->created : $invitatie->created->format("Y-m-j H:i:s");
      $listaInvitatii[] = $invitatieDisplay;
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'form' => $form->createView(),
      'navigation' => UtilsController::getNavigation('fondVanatoare'),
      'listaAgenti' => $listaUseri,
      'listaInvitatii' => $listaInvitatii,
      'fondVanatoare' => $fondVanatoare,
      'unitateJudeteana' => $unitateJudeteana->name
    );
    return $this->render('cms/fondvanatoareedit.html.twig', $params);
  }
  
  /**
   * Matches /cms/rapoarte exactly
   *
   * @Route("/cms/rapoarte", name="cmsRapoarte")
   * @Method({"GET"})
   *
   */
  public function cmsRapoarte(Request $request) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentJudetean && !$user->isAgentMinister) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    $unitateJudeteanaRepository = $dm->getRepository('AppBundle:UnitateJudeteana');
    $unitateJudeteana = $unitateJudeteanaRepository->findOneById($user->unitateJudeteana);

    $fondVanatoareRepository = $dm->getRepository('AppBundle:FondVanatoare');
    $userRepository = $dm->getRepository('AppBundle:User');

    $cursorRapoarte = $dm->createQueryBuilder('AppBundle:Raport')
      ->field('unitateJudeteana')->equals($unitateJudeteana->getId())
      ->sort('created', 'desc')
      ->getQuery()->execute();
    
    $listaRapoarte = array();
    $nr = 0;
    foreach($cursorRapoarte as $raport) {
      $nr++;
      $fondVanatoare = $fondVanatoareRepository->findOneById($raport->fondVanatoare);
      $agentColector = $userRepository->findOneById($raport->userId);

      $listaRapoarte[] = array(
        'id' => $raport->getId(),
        'nr' => $nr,
        'fondVanatoare' => $fondVanatoare != null ? $fondVanatoare->name : "",
        'agent' => $agentColector->getFullName(),
        'dataGenerare' => is_string($raport->created) ? $raport->created : $raport->created->format('Y-m-j H:i:s')
      );
    }
    
    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('istoric'),
      'rapoarte' => $listaRapoarte,
      'unitateJudeteana' => $unitateJudeteana->name
    );
    return $this->render('cms/rapoarte.html.twig', $params);
  }
  
  /**
   * Matches /cms/rapoarte/{raportId} exactly
   *
   * @Route("/cms/rapoarte/{raportId}", name="cmsJudRapoarte")
   * @Method({"GET", "POST"})
   *
   */
  public function cmsJudRapoarte(Request $request, $raportId = null) {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isValidSession($request, $dm);

    if(!$user) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    if(!$user->isAgentJudetean && !$user->isAgentMinister) {
      $request->getSession()->invalidate();
      return $this->redirectToRoute('loginPage');
    }
    
    // $emptyForm = new EmptyForm();
    //
    // $form = $this->createFormBuilder($emptyForm)
    //     ->add('generare', SubmitType::class, array('label' => 'Generare raport'))
    //     ->getForm();
    // $form->handleRequest($request);

    // $butonGenerare = true;
    // $startTime = 0;
    // $finish = new \DateTime();
    // $finish->setTimestamp(time());
    // $butonNavigare = "raport";
    // $butonNavigare = "istoric";
    // $butonGenerare = false;
    
    $fondVanatoareRepository = $dm->getRepository('AppBundle:FondVanatoare');
    $userRepository = $dm->getRepository('AppBundle:User');
    $raportRepository = $dm->getRepository('AppBundle:Raport');
    $raport = $raportRepository->findOneById($raportId);
    if (!$raport) {
      return $this->redirectToRoute('cmsIstoric');
    }
    $fondVanatoare = $fondVanatoareRepository->findOneById($raport->fondVanatoare);
    $agentColector = $userRepository->findOneById($raport->userId);
    $speciesList = $raport->total;
    
    // if ($form->isSubmitted() && $form->isValid()) {
//
//       if ($form->get('generare')->isClicked()) {
//         $raportNou = new Raport();
//         $raportNou->userId = $user->getId();
//         $raportNou->fondVanatoare = $user->fondVanatoare;
//         $raportNou->unitateJudeteana = $user->unitateJudeteana;
//         $raportNou->created = $raportNou->updated = date("c");
//         $raportNou->total = $speciesList;
//         $dm->persist($raportNou);
//         $dm->flush();
//         return $this->redirectToRoute('cmsIstoric');
//       }
//     }

    $params = array(
      'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
      'user' => $user,
      'navigation' => UtilsController::getNavigation('rapoarte'),
      'listaSpecii' => $speciesList,
      'fondVanatoare' => $fondVanatoare->name,
      'agent' => $agentColector->getFullName()
      // 'generare' => $butonGenerare,
      // 'form' => $form->createView()
    );
    return $this->render('cms/raportedit.html.twig', $params);
  }
  
}