<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class Species
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
   * @MongoDB\Field(type="date")
   */
  public $created;
  
  /**
   * @MongoDB\Field(type="collection")
   */
  public $diviziune = array();
  
  /**
   * @MongoDB\Field(type="collection")
   */
  public $detalii = array();
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $active = true;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $fisa = true;
  
  public function __construct() {
    $this->created = date("c");
  }
  
  public function getId() {
    return $this->id;
  }
  
  public function setId($id) {
    $this->id = $id;
  }
    
  public function display() {
    $divList = array();
    foreach($this->diviziune as $div)
      $divList[] = array(
        'id' => (String)$div['id'],
        'name' => $div['name']
      );
    return array(
      'id' => $this->id,
      'name' => $this->name,
      'active' => $this->active,
      'diviziune' => $divList,
      'fisa' => $this->fisa,
      'detalii' => $this->detalii,
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
    );
  }
  
  public function getSubspecieName($id) {
    foreach($this->diviziune as $div)
      if((String)$div['id'] == $id)
        return $div['name'];
    return "";
  }
}