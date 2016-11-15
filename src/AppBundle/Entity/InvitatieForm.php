<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class InvitatieForm
{
  
  /**
   * @Assert\NotBlank()
   * @Assert\Email()
   */
  public $email;
    
  /**
   * @Assert\NotBlank()
   * @Assert\Type("String")
   */
  public $fondVanatoare;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("String")
   */
  public $unitateJudeteana;
  
}