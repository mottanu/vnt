<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class Autorizatie
{
  /**
    * @MongoDB\Id
    */
  protected $id;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $numar;
  
  /**
   * @MongoDB\Field(type="integer")
   */
  public $tip;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $userId;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $organizator;

  /**
   * @MongoDB\Field(type="hash")
   */
  public $cote = array();
  
  /**
   * @MongoDB\Field(type="collection")
   */
  public $vanatori;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $dataInceput;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $dataSfarsit;
  
  /**
   * @MongoDB\Field(type="date")
   */
  public $created;
  
  /**
   * @MongoDB\Field(type="int")
   */
  public $contor;
  
  /**
   * @MongoDB\Field(type="int")
   */
  public $an;
  
  public function getId() {
    return $this->id;
  }
    
  public function display() {
    return array(
      'id' => $this->id,
      'userId' => $this->userId,
      'numar' => $this->numar,
      'organizator' => $this->organizator,
      'cote' => $this->cote,
      'vanatori' => $this->vanatori,
      'contor' => $this->contor,
      'an' => $this->an,
      'dataInceput' => is_string($this->dataInceput) ? $this->dataInceput : $this->dataInceput->format('c'),
      'dataSfarsit' => is_string($this->dataSfarsit) ? $this->dataSfarsit : $this->dataSfarsit->format('c'),
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
      'createdWeb' => is_string($this->created) ? $this->created : $this->created->format('Y-m-j H:i:s')
    );
  }
}