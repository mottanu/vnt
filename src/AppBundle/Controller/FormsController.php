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
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use AppBundle\Entity\UserSignup;
use AppBundle\Entity\UserLogin;
use Symfony\Component\Form\FormError;

class FormsController extends Controller
{
  
  /**
   * Matches /signup exactly
   *
   * @Route("/signup", name="signupForm")
   * @Method({"GET", "POST"})
   *
   */
  public function signupForm(Request $request) {

    $userSignup = new UserSignup();
    

    $form = $this->createFormBuilder($userSignup)
        ->add('email', TextType::class, array(
          'required' => true,
          'label'  => 'Email'))
        ->add('password', RepeatedType::class, array(
          'type' => PasswordType::class,
          'first_options'  => array('label' => 'Password'),
          'second_options' => array('label' => 'Repeat Password'),
          'label' => 'Password',
          'required' => true))
        ->add('firstname', TextType::class, array(
          'required' => true,
          'label'  => 'Firstname'))
        ->add('lastname', TextType::class, array(
          'required' => true,
          'label'  => 'Lastname'))
        ->add('invitationCode', HiddenType::class, array(
          'data' => $request->query->get('code')))
        ->add('signup', SubmitType::class, array('label' => 'Signup'))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      
      $userSignup = $form->getData();
      
      $dm = $this->get('doctrine_mongodb')->getManager();
      $userRepository = $dm->getRepository('AppBundle:User');
      $invitationCodeRepository = $dm->getRepository('AppBundle:InvitationCode');
    
      $checkInvitationCode = $invitationCodeRepository->findOneByCode($userSignup->invitationCode);
      if (!$checkInvitationCode || !$checkInvitationCode->active)
        $form->get('email')->addError(new FormError('Invalid invitation code!'));
      else {
        $checkUser = $userRepository->findOneByEmail($userSignup->email);
        if ($checkUser)
          $form->get('email')->addError(new FormError('Email address is already in use!'));
        else {
          if($userSignup->email != $checkInvitationCode->email)
            $form->get('email')->addError(new FormError('Invalid invitation code for given email address!'));
          else {
            $passwordArray = UtilsController::encodePassword($userSignup->password);

            $newUser = new User();
            $newUser->email = $userSignup->email;
            $newUser->password = $passwordArray[1];
            $newUser->passwordSalt = $passwordArray[0];
            $newUser->firstname = $userSignup->firstname;
            $newUser->lastname = $userSignup->lastname;
            $newUser->unitateJudeteana = $checkInvitationCode->unitateJudeteana;
            $newUser->fondVanatoare = $checkInvitationCode->fondVanatoare;
            $newUser->isAgentColector = true;
            $newUser->created = $newUser->updated = date("c");
            $dm->persist($newUser);

            $checkInvitationCode->active = false;

            $dm->flush();

            $emailParams = array(
              'firstname' => $userSignup->firstname,
              'lastname' => $userSignup->lastname,
            );
            UtilsController::sendEmail('Welcome email', $userSignup->email, 'welcome', $emailParams);

            return $this->redirectToRoute('homepage');
          }
        }
      }
    }

    return $this->render('signup.html.twig', array(
        'form' => $form->createView(),
    ));
  }
  
  /**
   * Matches /login exactly
   *
   * @Route("/login", name="loginPage")
   * @Method({"GET", "POST"})
   *
   */
  public function loginPage(Request $request) {

    $session = $request->getSession();
    $userLogin = new UserLogin();

    $form = $this->createFormBuilder($userLogin)
        ->add('email', TextType::class, array(
          'required' => true,
          'label'  => 'Email'))
        ->add('password', PasswordType::class, array(
          'label' => 'Password',
          'required' => true))
        ->add('login', SubmitType::class, array('label' => 'Login'))
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      
      $userLogin = $form->getData();
      
      $dm = $this->get('doctrine_mongodb')->getManager();
      $userRepository = $dm->getRepository('AppBundle:User');
    
      $checkUser = $userRepository->findOneByEmail($userLogin->email);
      if (!$checkUser)
        $form->get('email')->addError(new FormError('Nu exista user cu aceasta adresa de email!'));
      else {
        
        if(!UtilsController::validatePassword($userLogin->password, $checkUser->password, $checkUser->passwordSalt))
          $form->get('password')->addError(new FormError('Parola este gresita!'));
        else {
          $checkUser->lastLogin = date("c");
          
          $newToken = new Token();
          $newToken->userId = $checkUser->getId();
          $newToken->token = UtilsController::generateToken();
          $dm->persist($newToken);

          $dm->flush();
          
          $session->set('apiKey', $newToken->token);
          $session->set('apiKeyLast', time());

          $redirectRoute = 'cmsDashboard';
          if($checkUser->isAdmin)
            $redirectRoute = 'adminDashboard';
          else if($checkUser->isAgentMinister)
            $redirectRoute = 'amDashboard';
          else if($checkUser->isAgentJudetean)
            $redirectRoute = 'ajDashboard';
          else if($checkUser->isAgentFondVanatoare)
            $redirectRoute = 'afDashboard';
          else if($checkUser->isAgentColector)
            $redirectRoute = 'acDashboard';
          return $this->redirectToRoute($redirectRoute);
        }
      }
    }

    return $this->render('login.html.twig', array(
        'form' => $form->createView(),
    ));
  }
  
}