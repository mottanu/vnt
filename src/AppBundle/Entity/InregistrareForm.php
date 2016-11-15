<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class InregistrareForm
{
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("integer")
   */
  public $nr;
    
  /**
   * @Assert\NotBlank()
   * @Assert\Type("String")
   */
  public $inregistrareId;
  
  /**
   * @Assert\Type("String")
   */
  public $observatii;
  
}