<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class UserSignup
{
  
  /**
   * @Assert\Type("String")
   */
  public $invitationCode;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Email()
   */
  public $email;
    
  /**
   * @Assert\NotBlank()
   * @Assert\Type("String")
   * @Assert\Length(min=8)
   */
  public $password;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("String")
   */
  public $firstname;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("String")
   */
  public $lastname;
  
  public $address;
  
  public $city;
}