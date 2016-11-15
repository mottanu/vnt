<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class EmptyForm
{
  
  /**
   * @Assert\Type("String")
   */
  public $empty;
  
}