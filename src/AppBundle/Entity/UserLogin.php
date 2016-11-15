<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class UserLogin
{
  
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
  
}