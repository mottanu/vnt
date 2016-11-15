<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class User 
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
  public $password;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $passwordSalt;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $firstname;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $lastname;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $address;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $city;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $device;
  
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
  public $lastLogin;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $active = true;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $isAdmin = false;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $isAgentColector = false;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $isAgentJudetean = false;
  
  /**
   * @MongoDB\Field(type="bool")
   */
  public $isAgentMinister = false;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $unitateJudeteana;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $fondVanatoare;
  
  public function getId() {
    return $this->id;
  }
  
  public function getFullName() {
    return $this->firstname . ' ' . $this->lastname;
  }
    
  public function display() {
    return array(
      'id' => $this->id,
      'email' => $this->email,
      'firstname' => $this->firstname,
      'lastname' => $this->lastname,
      'address' => $this->address,
      'city' => $this->city,
      'device' => $this->device,
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
      'updated' => is_string($this->updated) ? $this->updated : $this->updated->format('c'),
      'active' => $this->active,
      'isAdmin' => $this->isAdmin,
      'isAgentColector' => $this->isAgentColector,
      'isAgentJudetean' => $this->isAgentJudetean,
      'isAgentMinister' => $this->isAgentMinister,
      'unitateJudeteana' => $this->unitateJudeteana,
      'fondVanatoare' => $this->fondVanatoare
    );
  }
}