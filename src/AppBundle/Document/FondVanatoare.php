<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class FondVanatoare 
{
  /**
    * @MongoDB\Id
    */
  protected $id;

  /**
   * @MongoDB\Field(type="string")
   */
  public $name;
  
  /**
   * @MongoDB\Field(type="int")
   */
  public $suprafata;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $region;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $created;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $updated;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $unitateJudeteana;
  
  public function getId() {
    return $this->id;
  }
    
  public function display() {
    return array(
      'id' => $this->id,
      'name' => $this->name,
      'suprafata' => $this->suprafata,
      'region' => $this->region,
      'unitateJudeteana' => $this->unitateJudeteana,
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
      'updated' => is_string($this->updated) ? $this->updated : $this->updated->format('c')
    );
  }
}