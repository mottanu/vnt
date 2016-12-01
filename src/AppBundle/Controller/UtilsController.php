<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use AppBundle\Document\Category;
use AppBundle\Document\User;
use AppBundle\Document\UnitateJudeteana;
use AppBundle\Document\FondVanatoare;
use AppBundle\Document\Species;

class UtilsController extends Controller
{

  /**
   * Init data
   */
  public function initData() {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $dataCurenta = date("c");

    $repository = $dm->getRepository('AppBundle:User');
    $adminCursor = $repository->findBy(array('isAdmin' => true));
    if(count($adminCursor) == 0) {
      // Create default admin user
      $passwordArray = UtilsController::encodePassword("dandan12");
      $newAdmin = new User();
      $newAdmin->email = "dan@wearewip.com";
      $newAdmin->password = $passwordArray[1];
      $newAdmin->passwordSalt = $passwordArray[0];
      $newAdmin->firstname = "Admin";
      $newAdmin->lastname = "Dan";
      $newAdmin->created = $newAdmin->updated = $dataCurenta;
      $newAdmin->isAdmin = true;
      $newAdmin->isAgentColector = true;
      $newAdmin->isAgentJudetean = true;
      $newAdmin->isAgentMinister = true;
      $dm->persist($newAdmin);
    }

    $uJudRepository = $dm->getRepository('AppBundle:UnitateJudeteana');
    $uJudCursor = $uJudRepository->findAll();
    if(count($uJudCursor) == 0) {
      // Creare Unitate Judeteana Cluj
      $uJud1 = new UnitateJudeteana();
      $uJud1->name = "Cluj";
      $uJud1->region = "CJ";
      $uJud1->suprafata = 599407;
      $uJud1->created = $uJud1->updated = $dataCurenta;
      $dm->persist($uJud1);
      // Creare Fond Vanatoare
      $fVan1 = new FondVanatoare();
      $fVan1->name = "FLORESTI";
      $fVan1->region = "CJ";
      $fVan1->suprafata = 10366;
      $fVan1->unitateJudeteana = $uJud1->getId();
      $fVan1->created = $fVan1->updated = $dataCurenta;
      $dm->persist($fVan1);
      // Creare Fond Vanatoare
      $fVan2 = new FondVanatoare();
      $fVan2->name = "BAISOARA";
      $fVan2->region = "CJ";
      $fVan2->suprafata = 19679;
      $fVan2->unitateJudeteana = $uJud1->getId();
      $fVan2->created = $fVan2->updated = $dataCurenta;
      $dm->persist($fVan2);
      // Creare Unitate Judeteana Bistrita-Nasaud
      $uJud2 = new UnitateJudeteana();
      $uJud2->name = "Bistrita-Nasaud";
      $uJud2->region = "BN";
      $uJud2->suprafata = 496887;
      $uJud2->created = $uJud2->updated = $dataCurenta;
      $dm->persist($uJud2);
      // Creare Fond Vanatoare
      $fVan3 = new FondVanatoare();
      $fVan3->name = "TELCIU";
      $fVan3->region = "BN";
      $fVan3->suprafata = 13023;
      $fVan3->unitateJudeteana = $uJud2->getId();
      $fVan3->created = $fVan3->updated = $dataCurenta;
      $dm->persist($fVan3);
      // Creare Fond Vanatoare
      $fVan4 = new FondVanatoare();
      $fVan4->name = "LUNCA ILVEI";
      $fVan4->region = "BN";
      $fVan4->suprafata = 11759;
      $fVan4->unitateJudeteana = $uJud2->getId();
      $fVan4->created = $fVan4->updated = $dataCurenta;
      $dm->persist($fVan4);
      // Creare Unitate Judeteana Ilfov
      $uJud3 = new UnitateJudeteana();
      $uJud3->name = "Ilfov";
      $uJud3->region = "IF";
      $uJud3->suprafata = 157752;
      $uJud3->created = $uJud3->updated = $dataCurenta;
      $dm->persist($uJud3);
      // Creare Fond Vanatoare
      $fVan5 = new FondVanatoare();
      $fVan5->name = "SNAGOV";
      $fVan5->region = "IF";
      $fVan5->suprafata = 5005;
      $fVan5->unitateJudeteana = $uJud3->getId();
      $fVan5->created = $fVan5->updated = $dataCurenta;
      $dm->persist($fVan5);
    }

    $speciesRepository = $dm->getRepository('AppBundle:Species');
    $speciesCursor = $speciesRepository->findAll();
    if(count($speciesCursor) == 0) {
      // Creare specie Cerb comun
      $specie1 = new Species();
      $specie1->name = "Cerb comun";
      $specie1->created = $dataCurenta;
      $diviziune1 = array();
      $diviziune1[] = array(
        'id' => new \MongoId(),
        'name' => "Masculi, Trofeu"
      );
      $diviziune1[] = array(
        'id' => new \MongoId(),
        'name' => "Masculi, Selectie"
      );
      $diviziune1[] = array(
        'id' => new \MongoId(),
        'name' => "Femele, Tineret"
      );
      $specie1->diviziune = $diviziune1;
      $dm->persist($specie1);
      // Creare specie Cerb lopatar
      $specie2 = new Species();
      $specie2->name = "Cerb lopatar";
      $specie2->created = $dataCurenta;
      $diviziune2 = array();
      $diviziune2[] = array(
        'id' => new \MongoId(),
        'name' => "Masculi, Trofeu"
      );
      $diviziune2[] = array(
        'id' => new \MongoId(),
        'name' => "Masculi, Selectie"
      );
      $diviziune2[] = array(
        'id' => new \MongoId(),
        'name' => "Femele, Tineret"
      );
      $specie2->diviziune = $diviziune2;
      $dm->persist($specie2);
      // Creare specie Mistret
      $specie3 = new Species();
      $specie3->name = "Mistret";
      $specie3->created = $dataCurenta;
      $dm->persist($specie3);
      // Creare specie Iepure
      $specie4 = new Species();
      $specie4->name = "Iepure";
      $specie4->created = $dataCurenta;
      $dm->persist($specie4);
      // Creare specie Fazan
      $specie5 = new Species();
      $specie5->name = "Fazan";
      $specie5->created = $dataCurenta;
      $dm->persist($specie5);
    }

    $dm->flush();
  }
  
  /**
   * Init data
   */
  public function initNewUsersData() {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $dataCurenta = date("c");

    $repository = $dm->getRepository('AppBundle:User');
    $userCursor = $repository->findBy(array('isAgentColector' => true));
    if(count($userCursor) == 0) {
      // Create default agent colector user
      $passwordArray = UtilsController::encodePassword("p@ss1234");
      $newAdmin = new User();
      $newAdmin->email = "colector@mm-vanatoare.ro";
      $newAdmin->password = $passwordArray[1];
      $newAdmin->passwordSalt = $passwordArray[0];
      $newAdmin->firstname = "Agent";
      $newAdmin->lastname = "Colector";
      $newAdmin->created = $newAdmin->updated = $dataCurenta;
      $newAdmin->isAdmin = false;
      $newAdmin->isAgentColector = true;
      $newAdmin->isAgentFondVanatoare = false;
      $newAdmin->isAgentJudetean = false;
      $newAdmin->isAgentMinister = false;
      $dm->persist($newAdmin);
    }

    $userCursor = $repository->findBy(array('isAgentFondVanatoare' => true));
    if(count($userCursor) == 0) {
      // Create default agent colector user
      $passwordArray = UtilsController::encodePassword("p@ss1234");
      $newAdmin = new User();
      $newAdmin->email = "fondvanatoare@mm-vanatoare.ro";
      $newAdmin->password = $passwordArray[1];
      $newAdmin->passwordSalt = $passwordArray[0];
      $newAdmin->firstname = "Agent";
      $newAdmin->lastname = "Fond Vanatoare";
      $newAdmin->created = $newAdmin->updated = $dataCurenta;
      $newAdmin->isAdmin = false;
      $newAdmin->isAgentColector = false;
      $newAdmin->isAgentFondVanatoare = true;
      $newAdmin->isAgentJudetean = false;
      $newAdmin->isAgentMinister = false;
      $dm->persist($newAdmin);
    }
    
    $userCursor = $repository->findBy(array('isAgentJudetean' => true));
    if(count($userCursor) == 0) {
      // Create default agent colector user
      $passwordArray = UtilsController::encodePassword("p@ss1234");
      $newAdmin = new User();
      $newAdmin->email = "jud@mm-vanatoare.ro";
      $newAdmin->password = $passwordArray[1];
      $newAdmin->passwordSalt = $passwordArray[0];
      $newAdmin->firstname = "Agent";
      $newAdmin->lastname = "Judetean";
      $newAdmin->created = $newAdmin->updated = $dataCurenta;
      $newAdmin->isAdmin = false;
      $newAdmin->isAgentColector = false;
      $newAdmin->isAgentFondVanatoare = false;
      $newAdmin->isAgentJudetean = true;
      $newAdmin->isAgentMinister = false;
      $dm->persist($newAdmin);
    }
    
    $userCursor = $repository->findBy(array('isAgentMinister' => true));
    if(count($userCursor) == 0) {
      // Create default agent colector user
      $passwordArray = UtilsController::encodePassword("p@ss1234");
      $newAdmin = new User();
      $newAdmin->email = "minister@mm-vanatoare.ro";
      $newAdmin->password = $passwordArray[1];
      $newAdmin->passwordSalt = $passwordArray[0];
      $newAdmin->firstname = "Agent";
      $newAdmin->lastname = "Minister";
      $newAdmin->created = $newAdmin->updated = $dataCurenta;
      $newAdmin->isAdmin = false;
      $newAdmin->isAgentColector = false;
      $newAdmin->isAgentFondVanatoare = false;
      $newAdmin->isAgentJudetean = false;
      $newAdmin->isAgentMinister = true;
      $dm->persist($newAdmin);
    }
    
    $userCursor = $repository->findBy(array('isAdmin' => true));
    if(count($userCursor) == 0) {
      // Create default agent colector user
      $passwordArray = UtilsController::encodePassword("p@ss1234");
      $newAdmin = new User();
      $newAdmin->email = "admin@mm-vanatoare.ro";
      $newAdmin->password = $passwordArray[1];
      $newAdmin->passwordSalt = $passwordArray[0];
      $newAdmin->firstname = "User";
      $newAdmin->lastname = "Admin";
      $newAdmin->created = $newAdmin->updated = $dataCurenta;
      $newAdmin->isAdmin = true;
      $newAdmin->isAgentColector = false;
      $newAdmin->isAgentFondVanatoare = false;
      $newAdmin->isAgentJudetean = false;
      $newAdmin->isAgentMinister = false;
      $dm->persist($newAdmin);
    }

    $dm->flush();
  }

  /**
   * Check if $value exists and is empty
   */
  public function isEmpty($key, $params = null) {
    if($params != null) {
      if(array_key_exists($key, $params) && !empty(trim($params[$key])))
        return false;
    }
    else if(!empty(trim($key)))
      return false;

    return true;
  }

  /**
   * Return error response
   */
  public function error($error, $errorCode = 400) {
    $resp = array(
      'ok' => false,
      'errorCode' => $errorCode,
      'error' => $error
    );
    $errorResponse = new JsonResponse($resp);
    $errorResponse->setStatusCode(400);
    return $errorResponse;
  }

  /**
   * Generate an unique activation code
   */
  public function generateInvitationCode() {
    return hash('sha256', base64_encode(random_bytes(128)) . time());
  }

  /**
   * Generate an unique token
   */
  public function generateToken() {
    return hash('sha256', base64_encode(random_bytes(32)) . time());
  }

  /**
   * Generate an unique activation code
   */
  public function generateRecoverCode() {
    return hash('sha256', base64_encode(random_bytes(128)) . time());
  }

  /**
   * Encode the stored password
   */
  public function encodePassword($password) {
    $randomSalt = base64_encode(random_bytes(32));
    $encoded = hash('sha256', $randomSalt . $password);
    return array($randomSalt, $encoded);
  }

  /**
   * Validate password
   */
  public function validatePassword($password, $userPassword, $userPasswordSalt) {
    $encoded = hash('sha256', $userPasswordSalt . $password);
    return $encoded == $userPassword;
  }

  public function sendEmail($subject, $to, $type, $params = array()) {
    $from = $this->getParameter('senderEmail');

    switch($type) {
      case 'welcome': $layout = 'emails/welcome.html.twig'; break;
      case 'invitation': $layout = 'emails/invitation.html.twig'; break;
      default: $layout = 'emails/welcome.html.twig'; break;
    }

    $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($this->renderView($layout, $params), 'text/html');
    $this->get('mailer')->send($message);
  }

  /**
   * Check access token
   */
  public function isAuthenticated($request, $dm = null) {
    $user = null;

    $apiKeyHeaders = $request->headers->get('authorization');
    if(!empty($apiKeyHeaders)) {
      $apiKeyArray = explode(" ", $apiKeyHeaders);
      $apiKey = $apiKeyArray[1];
    }

    if(empty($apiKey)) {
      $apiKey = $request->request->get('access_token');
    }

    if(empty($apiKey)) {
      $apiKey = $request->query->get('access_token');
    }

    if(empty($apiKey))
      return null;

    if($dm == null)
      $dm = $this->get('doctrine_mongodb')->getManager();

    $tokenRepository = $dm->getRepository('AppBundle:Token');
    $userRepository = $dm->getRepository('AppBundle:User');

    $accessToken = $tokenRepository->findOneByToken($apiKey);
    if (!$accessToken)
      return null;

    // TO DO: Verificare expirare acess token

    $user = $userRepository->findOneById($accessToken->userId);
    if(!$user)
      return null;

    if(!$user->active)
      return null;

    return $user;
  }

  /**
   * Check CMS User session
   */
  public function isValidSession($request, $dm = null) {
    $user = null;

    $session = $request->getSession();
    $apiKey = $session->get('apiKey');
    if(empty($apiKey))
      return null;

    $apiKeyLast = $session->get('apiKeyLast');
    if(empty($apiKeyLast) || time() - $apiKeyLast > 86400)
      return null;

    if($dm == null)
      $dm = $this->get('doctrine_mongodb')->getManager();

    $tokenRepository = $dm->getRepository('AppBundle:Token');
    $userRepository = $dm->getRepository('AppBundle:User');

    $accessToken = $tokenRepository->findOneByToken($apiKey);
    if (!$accessToken)
      return null;

    // TO DO: Verificare expirare acess token

    $user = $userRepository->findOneById($accessToken->userId);
    if(!$user)
      return null;

    if(!$user->active)
      return null;

    if($user != null)
      $session->set('apiKeyLast', time());

    return $user;
  }

  /**
   * Get navigation
   */
  public function getNavigation($page = null) {
    $navigation = array(
      'dashboard' => "",
      'inregistrari' => "",
      'raport' => "",
      "istoric" => "",
      'fondVanatoare' => "",
      'rapoarte' => "",
      'cote' => "",
      'autorizatii' => ""
    );

    switch($page) {
      case "dashboard": $navigation['dashboard'] = "current-page"; break;
      case "inregistrari": $navigation['inregistrari'] = "current-page"; break;
      case "raport": $navigation['raport'] = "current-page"; break;
      case "istoric": $navigation['istoric'] = "current-page"; break;
      case "fondVanatoare": $navigation['fondVanatoare'] = "current-page"; break;
      case "rapoarte": $navigation['rapoarte'] = "current-page"; break;
      case "cote": $navigation['cote'] = "current-page"; break;
      case "autorizatii": $navigation['autorizatii'] = "current-page"; break;
      case "autorizatieadauga": $navigation['autorizatieadauga'] = "current-page"; break;
      default: $navigation['dashboard'] = "current-page"; break;
    }

    return $navigation;
  }

  /**
   * Get specie
   */
  public function getSpecie($speciesId, $dm = null) {
    $specie = null;

    if($dm == null)
      $dm = $this->get('doctrine_mongodb')->getManager();

    $speciesRepository = $dm->getRepository('AppBundle:Species');
    $species1 = $speciesRepository->findOneById($speciesId);
    if($species1 != null)
      return $species1;

    $species2 = $speciesRepository->findOneBy(array("diviziune.id" => new \MongoId($speciesId)));
    if($species2 != null) {
      $subspecie = new Species();
      $subspecie->setId($speciesId);
      $subspecie->name = $species2->name . ", " . $species2->getSubspecieName($speciesId);
      return $subspecie;
    }
    return null;
  }

  /**
   * Get specie
   */
  public function getSpeciesList($dm) {
    $speciesList = array();

    if($dm == null)
      $dm = $this->get('doctrine_mongodb')->getManager();

    $speciesRepository = $dm->getRepository('AppBundle:Species');
    $speciesCursor = $speciesRepository->findAll();

    foreach($speciesCursor as $specie) {
      if(count($specie->diviziune) == 0)
        $speciesList[$specie->getId()] = array('name' => $specie->name, 'total' => 0);
      else {
        $speciesList[$specie->getId()] = array('name' => $specie->name, 'total' => 0);
        foreach($specie->diviziune as $div)
          $speciesList[''.$div['id']] = array('name' => $specie->name . ', ' . $div['name'], 'total' => 0);
      }
    }

    return $speciesList;
  }
  
  public function getTipuriAutorizatie() {
    $tipuri = array(
      "Individuala" => 0,
      "Grup restrans" => 1,
      "Colectiva" => 2
    );

    return $tipuri;
  }
  
  public function getUrmatorulNumarAutorizatie($dm) {
    if($dm == null)
      $dm = $this->get('doctrine_mongodb')->getManager();

    $cursorAutorizatii = $dm->createQueryBuilder('AppBundle:Autorizatie')
      ->sort('created', 'desc')
      ->limit(1)
      ->getQuery()->execute();
  
    $ultimulContor = 0;
    if ($cursorAutorizatii->count() > 0) {
        $cursorAutorizatii->next();
        $ultimaAutorizatie = $cursorAutorizatii->current();
        $ultimulContor = $ultimaAutorizatie->contor;
    } 
  
    return $ultimulContor + 1;
  }
  
  public function getSerieAgentFondVanatoare($serie) {
    $serieString = "";
    if($serie < 10)
      $serieString = "000" . $serie;
    else if($serie < 100)
      $serieString = "00" . $serie;
    else if($serie < 1000)
      $serieString = "0" . $serie;
    else
      $serieString = "" . $serie;
  
    return $serieString;
  }
  
  public function getContor($contor) {
    $contorString = "";
    if($contor < 10)
      $contorString = "000" . $contor;
    else if($contor < 100)
      $contorString = "00" . $contor;
    else if($contor < 1000)
      $contorString = "0" . $contor;
    else
      $contorString = "" . $contor;
  
    return $contorString;
  }
}
