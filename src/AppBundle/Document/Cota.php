<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class Cota
{
  /**
    * @MongoDB\Id
    */
  protected $id;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $speciesId;

  /**
   * @MongoDB\Field(type="string")
   */
  public $userId;
  
  /**
   * @MongoDB\Field(type="int")
   */
  public $nr;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $userAprobareId;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $created;
  
  public function getId() {
    return $this->id;
  }
    
  public function display() {
    return array(
      'id' => $this->id,
      'userId' => $this->userId,
      'speciesId' => $this->speciesId,
      'nr' => $this->nr,
      'userAprobareId' => $this->userAprobareId,
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
      'createdWeb' => is_string($this->created) ? $this->created : $this->created->format('Y-m-j H:i:s')
    );
  }
}