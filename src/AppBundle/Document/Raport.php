<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class Raport
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
  public $fondVanatoare;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $unitateJudeteana;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $created;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $updated;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $dataFinalizare;
  
  /**
   * @MongoDB\Field(type="hash")
   */
  public $total =  array();
  
  /**
   * @MongoDB\Field(type="hash")
   */
  public $istoric =  array();
  
  public function getId() {
    return $this->id;
  }
    
  public function display() {
    return array(
      'id' => $this->id,
      'userId' => $this->userId,
      'fondVanatoare' => $this->fondVanatoare,
      'unitateJudeteana' => $this->unitateJudeteana,
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
      'updated' => is_string($this->updated) ? $this->updated : $this->updated->format('c'),
      'dataFinalizare' => is_string($this->dataFinalizare) ? $this->dataFinalizare : $this->dataInregistrare->format('c'),
      'total' => $this->total,
      'istoric' => $this->istoric
    );
  }
}