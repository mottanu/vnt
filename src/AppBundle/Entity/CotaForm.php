<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class CotaForm
{
  
  /**
   * @Assert\Type("String")
   */
  public $userId;
    
  /**
   * @Assert\NotBlank()
   * @Assert\Type("String")
   */
  public $speciesId;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("Integer")
   */
  public $nr;
  
}