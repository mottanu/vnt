<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class InvitationCode 
{
  /**
    * @MongoDB\Id
    */
  protected $id;

  /**
   * @MongoDB\Field(type="string")
   */
  public $email;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $unitateJudeteana;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $fondVanatoare;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $code;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $created;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $accessDate = null;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $expires;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $active = true;
  
  public function getId() {
    return $this->id;
  }
    
  public function display() {
    return array(
      'email' => $this->email,
      'unitateJudeteana' => $this->unitateJudeteana,
      'fondVanatoare' => $this->fondVanatoare,
      'code' => $this->code,
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
    );
  }
}