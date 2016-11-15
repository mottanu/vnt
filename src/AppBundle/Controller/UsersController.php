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

class UsersController extends Controller
{

  /**
   * Matches /v1/users/login exactly
   *
   * @Route("/v1/users/login", name="login")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="User login.",
   *     description="User login.",
   *     section="Users",
   *     parameters={
   *       {"name"="email", "dataType"="string", "required"="true", "source"="body", "description"="User email"},
   *       {"name"="password", "dataType"="string", "required"="true", "source"="body", "description"="Password"},
   *     }
   *  )
   */
  public function login(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $tokenRepository = $dm->getRepository('AppBundle:Token');

    $email = $request->request->get('email');
    $password = $request->request->get('password');

    if(empty($email))
      return UtilsController::error("Campul email este necesar!", 401);
    $email = trim($email);
    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
      return UtilsController::error("Campul email nu este valid!", 402);

     if(empty($password))
      return UtilsController::error("Campul parola este necesar!", 401);
    $password = trim($password);
    if(strlen($password) < 8)
      return UtilsController::error("Parola trebuie sa aiba cel putin 8 caractere!", 402);

    $checkUser = $userRepository->findOneByEmail($email);
    if (!$checkUser)
      return UtilsController::error("Nu exista un utilizator cu adresa de email specificata!", 403);

    if(!UtilsController::validatePassword($password, $checkUser->password, $checkUser->passwordSalt))
      return UtilsController::error("Parola incorecta!", 403);

    $checkUser->lastLogin = date("c");

    $newToken = new Token();
    $newToken->userId = $checkUser->getId();
    $newToken->token = UtilsController::generateToken();
    $dm->persist($newToken);

    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'user' => $checkUser->display(),
        'token' => $newToken->display()
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/users/invite exactly
   *
   * @Route("/v1/users/invite", name="inviteUser")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="User invite.",
   *     description="User invite.",
   *     section="Users",
   *     parameters={
   *       {"name"="email", "dataType"="string", "required"="true", "source"="body", "description"="User email"},
   *       {"name"="unitateJudeteana", "dataType"="string", "required"="false", "source"="body", "description"="Unitate judeteana"},
   *       {"name"="fondVanatoare", "dataType"="string", "required"="false", "source"="body", "description"="Fond vanatoare"},
   *     }
   *  )
   */
  public function inviteUser(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();

    $user = UtilsController::isAuthenticated($request, $dm);
    if(!$user)
      return UtilsController::error("Token invalid!", 404);

    if(!$user->isAdmin && !$user->isAgentMinister && !$user->isAgentJudetean)
      return UtilsController::error("Utilizatorul nu are acces sa realizeze aceasta operatiune!", 406);

    $userRepository = $dm->getRepository('AppBundle:User');
    $invitationCodeRepository = $dm->getRepository('AppBundle:InvitationCode');
    $unitateJudeteanaRepository = $dm->getRepository('AppBundle:UnitateJudeteana');
    $fondVanatoareRepository = $dm->getRepository('AppBundle:FondVanatoare');

    $email = $request->request->get('email');
    $unitateJudeteana = $request->request->get('unitateJudeteana');
    $fondVanatoare = $request->request->get('fondVanatoare');

    if(empty($email))
      return UtilsController::error("Field email is required!", 401);
    $email = trim($email);
    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
      return UtilsController::error("Field email is not valid!", 402);

    $checkUser = $userRepository->findOneByEmail($email);
    if ($checkUser)
      return UtilsController::error("Adresa de email este deja folosita!");

    if(!empty($unitateJudeteana)) {
      $checkUnitateJudeteana = $unitateJudeteanaRepository->findById($unitateJudeteana);
      if (!$checkUnitateJudeteana)
        return UtilsController::error("Unitate teritoriala invalida!");
    }

    if(!empty($fondVanatoare)) {
      $checkFondVanatoare = $fondVanatoareRepository->findById($fondVanatoare);
      if (!$checkFondVanatoare)
        return UtilsController::error("Fond de vanatoare invalid!");
    }

    $checkInvitationCode = $invitationCodeRepository->findOneByEmail($email);
    if ($checkInvitationCode) {
      $checkInvitationCode->unitateJudeteana = $unitateJudeteana;
      $checkInvitationCode->fondVanatoare = $fondVanatoare;
      $checkInvitationCode->code = UtilsController::generateInvitationCode();
    }
    else {
      $checkInvitationCode = new InvitationCode();
      $checkInvitationCode->email = $email;
      $checkInvitationCode->unitateJudeteana = $unitateJudeteana;
      $checkInvitationCode->fondVanatoare = $fondVanatoare;
      $checkInvitationCode->code = UtilsController::generateInvitationCode();
      $checkInvitationCode->created = date("c");
      $dm->persist($checkInvitationCode);
    }

    $dm->flush();

    $emailParams = array(
      'invitationCode' => $checkInvitationCode->code
    );
    UtilsController::sendEmail('Email invitation', $email, 'invitation', $emailParams);

    $resp = array(
      'ok' => true,
      'result' => array(
        'invitationCode' => $checkInvitationCode->display(),
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/users/signup exactly
   *
   * @Route("/v1/users/signup", name="signup")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="User create.",
   *     description="User create.",
   *     section="Users",
   *     parameters={
   *       {"name"="invitationCode", "dataType"="string", "required"="true", "source"="body", "description"="Invitation Code"},
   *       {"name"="email", "dataType"="string", "required"="true", "source"="body", "description"="User email"},
   *       {"name"="password", "dataType"="string", "required"="true", "source"="body", "description"="Password"},
   *       {"name"="phone", "dataType"="string", "required"="false", "source"="body", "description"="Phone"},
   *       {"name"="firstname", "dataType"="string", "required"="false", "source"="body", "description"="Firstname"},
   *       {"name"="lastname", "dataType"="string", "required"="false", "source"="body", "description"="Lastname"}
   *     }
   *  )
   */
  public function signup(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $tokenRepository = $dm->getRepository('AppBundle:Token');
    $invitationCodeRepository = $dm->getRepository('AppBundle:InvitationCode');

    $invitationCode = $request->request->get('invitationCode');
    $email = $request->request->get('email');
    $password = $request->request->get('password');
    $phone = $request->request->get('phone');
    $firstname = $request->request->get('firstname');
    $lastname = $request->request->get('lastname');

    if(empty($invitationCode))
      return UtilsController::error("Campul cod invitatie este necesar!", 401);
    else {
      $checkInvitationCode = $invitationCodeRepository->findOneByCode($invitationCode);
      if (!$checkInvitationCode || !$checkInvitationCode->active)
        return UtilsController::error("Cod invitatie invalid!", 402);
    }

    if(empty($email))
      return UtilsController::error("Campul email este necesar", 401);
    $email = trim($email);
    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
      return UtilsController::error("Campul email este invalid!", 402);
    $checkUser = $userRepository->findOneByEmail($email);
    if ($checkUser)
      return UtilsController::error("Acest email a mai fost utilizat!");

    if($email != $checkInvitationCode->email)
      return UtilsController::error("Cod invitatie invalid pentru adresa de mail specificata!", 404);

    if(empty($password))
      return UtilsController::error("Campul parola este necesar!", 401);
    $password = trim($password);
    if(strlen($password) < 8)
      return UtilsController::error("Parola trebuie sa aiba cel putin 8 caractere!", 402);

    $passwordArray = UtilsController::encodePassword($password);

    $newUser = new User();
    $newUser->email = $email;
    $newUser->password = $passwordArray[1];
    $newUser->passwordSalt = $passwordArray[0];
    $newUser->firstname = $firstname;
    $newUser->lastname = $lastname;
    $newUser->phone = $phone;
    $newUser->unitateJudeteana = $checkInvitationCode->unitateJudeteana;
    $newUser->fondVanatoare = $checkInvitationCode->fondVanatoare;
    $newUser->isAgentColector = true;
    $newUser->created = $newUser->updated = date("c");
    $dm->persist($newUser);

    $newToken = new Token();
    $newToken->userId = $newUser->getId();
    $newToken->token = UtilsController::generateToken();
    $dm->persist($newToken);

    $checkInvitationCode->active = false;

    $dm->flush();

    $emailParams = array(
      'firstname' => $firstname,
      'lastname' => $lastname,
    );
    UtilsController::sendEmail('Welcome email', $email, 'welcome', $emailParams);

    $resp = array(
      'ok' => true,
      'result' => array(
        'user' => $newUser->display(),
        'token' => $newToken->display()
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/users/forgot exactly
   *
   * @Route("/v1/users/forgot", name="forgot")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Forgot password.",
   *     description="Forgot password.",
   *     section="Users",
   *     parameters={
   *       {"name"="email", "dataType"="string", "required"="true", "source"="body", "description"="User email"},
   *     }
   *  )
   */
  public function forgot(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');

    $email =  $request->request->get('email');

    if(empty($email))
      return UtilsController::error("Campul email este necesar!");

    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
      return UtilsController::error("Campul email nu este valid!");

    $checkUser = $userRepository->findOneByEmail($email);
    if (!$checkUser)
      return UtilsController::error("Nu exista un utilizator cu adresa de mail specificata!");

    $newRecoverCode = new RecoverCode();
    $newRecoverCode->userId = $checkUser->getId();
    $newRecoverCode->code = UtilsController::generateRecoverCode();
    $newRecoverCode->created = date('c');
    $newRecoverCode->expires = date('c', time() + 900);
    $dm->persist($newRecoverCode);

    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'recoverCode' => $newRecoverCode
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/users/reset exactly
   *
   * @Route("/v1/users/reset", name="reset")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Reset password.",
   *     description="Reset password.",
   *     section="Users",
   *     parameters={
   *       {"name"="code", "dataType"="string", "required"="true", "source"="body", "description"="Recover code"},
   *       {"name"="password", "dataType"="string", "required"="true", "source"="body", "description"="New password"},
   *     }
   *  )
   */
  public function reset(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $userRepository = $dm->getRepository('AppBundle:User');
    $recoverRepository = $dm->getRepository('AppBundle:RecoverCode');

    $code =  $request->request->get('code');

    if(empty($code))
      return UtilsController::error("Campul cod este necesar!");

    $recoverCode = $recoverRepository->findOneByCode($code);
    if (!$recoverCode)
      return UtilsController::error("Cod recuperare invalid!");

    if($recoverCode->expires->getTimestamp() < time() || !$recoverCode->active)
      return UtilsController::error("Codul de recuperare a expirat!");

    if($recoverCode->accessDate != null)
      return UtilsController::error("Codul de recuperare a mai fost folosit!");

    $user = $userRepository->findOneById($recoverCode->userId);
    if (!$user)
      return UtilsController::error("Eroare validare utilizator!");

    $password =  $request->request->get('password');
    if(empty($password))
      return UtilsController::error("Campul parola este necesar!");
    $password = trim($password);
    if(strlen($password) < 8)
      return UtilsController::error("Password must have at least 8 characters!");

    $passwordArray = UtilsController::encodePassword($password);

    $user->password = $passwordArray[1];
    $user->passwordSalt = $passwordArray[0];
    $user->updated = date("c");

    $recoverCode->accessDate = date("c");
    $recoverCode->active = false;

    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'user' => $user
      )
    );
    return new JsonResponse($resp);
  }

  /**
   * Matches /v1/users/edit exactly
   *
   * @Route("/v1/users/edit", name="edit")
   * @Method({"POST"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="User edit.",
   *     description="User edit.",
   *     section="Users",
   *     parameters={
   *       {"name"="password", "dataType"="string", "required"="true", "source"="body", "description"="Password"},
   *       {"name"="phone", "dataType"="string", "required"="false", "source"="body", "description"="Phone"},
   *       {"name"="firstname", "dataType"="string", "required"="false", "source"="body", "description"="Firstname"},
   *       {"name"="lastname", "dataType"="string", "required"="false", "source"="body", "description"="Lastname"},
   *     }
   *  )
   */
  public function edit(Request $request) {

    $dm = $this->get('doctrine_mongodb')->getManager();
    $user = UtilsController::isAuthenticated($request, $dm);

    if(!$user)
      return UtilsController::error("Invalid token!", 404);

    $password =  $request->request->get('password');
    $firstname =  $request->request->get('firstname');
    $lastname =  $request->request->get('lastname');
    $phone =  $request->request->get('phone');

    if(!empty($password)) {
      if(strlen($password) < 8)
        return UtilsController::error("Password must have at least 8 characters!", 402);

      $passwordArray = UtilsController::encodePassword($password);
      $user->password = $passwordArray[1];
      $user->passwordSalt = $passwordArray[0];

      // TO DO : Invalidate all users tokens
    }

    if(!empty($firstname))
      $user->firstname = $firstname;
    if(!empty($lastname))
      $user->lastname = $lastname;
    if(!empty($phone))
      $user->phone = $phone;

    $dm->flush();

    $resp = array(
      'ok' => true,
      'result' => array(
        'user' => $user->display()
      )
    );
    return new JsonResponse($resp);
  }
  
  /**
   * Matches /v1/logout exactly
   *
   * @Route("/v1/logout", name="logout")
   * @Method({"GET"})
   *  
   */
  public function logout(Request $request) {

    $request->getSession()->invalidate();
    return $this->redirectToRoute('loginPage');
  }
}
