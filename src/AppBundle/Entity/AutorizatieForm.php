<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class AutorizatieForm
{
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("integer")
   */
  public $tip;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("string")
   */
  public $organizator;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("datetime")
   */
  public $dataInceput;
  
  /**
   * @Assert\NotBlank()
   * @Assert\Type("datetime")
   */
  public $dataSfarsit;
  
}