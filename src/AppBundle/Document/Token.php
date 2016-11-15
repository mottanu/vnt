<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class Token 
{
  /**
    * @MongoDB\Id
    */
  protected $id;

  /**
   * @MongoDB\Field(type="string")
   */
  public $userId;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $token;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $expires;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $isAdmin = false;
  
  public function getId() {
    return $this->id;
  }
    
  public function display() {
    return array(
      'token' => $this->token,
      'expires' => $this->expires,
      'isAdmin' => $this->isAdmin,
    );
  }
}