<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use JsonSerializable;

/**
 * @MongoDB\Document
 */
class Inregistrare
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
   * @MongoDB\Field(type="string")
   */
  public $fondVanatoare;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $unitateJudeteana;
  
  /**
   * @MongoDB\Field(type="int")
   */
  public $nr;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $observatii = "";
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $alteSpecii = "";
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $latitude;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $longitude;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $altitude;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $stareaTimpului;
  
  /**
   * @MongoDB\Field(type="hash")
   */
  public $detalii;
  
  /**
   * @MongoDB\Field(type="string")
   */
  public $versionCode;
  
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
  public $dataInregistrare;
  
  /**
   * @MongoDB\Field(type="collection")
   */
  public $files =  array();
  
  public function getId() {
    return $this->id;
  }
    
  public function display() {
    return array(
      'id' => $this->id,
      'userId' => $this->userId,
      'speciesId' => $this->speciesId,
      'fondVanatoare' => $this->fondVanatoare,
      'nr' => $this->nr,
      'observatii' => $this->observatii,
      'alteSpecii' => $this->alteSpecii,
      'stareaTimpului' => $this->stareaTimpului,
      'latitude' => $this->latitude,
      'longitude' => $this->longitude,
      'altitude' => $this->altitude,
      'detalii' => $this->detalii,
      'versionCode' => $this->versionCode,
      'created' => is_string($this->created) ? $this->created : $this->created->format('c'),
      'updated' => is_string($this->updated) ? $this->updated : $this->updated->format('c'),
      'dataInregistrare' => is_string($this->dataInregistrare) ? $this->dataInregistrare : $this->dataInregistrare->format('c'),
      'files' => $this->files
    );
  }
  
  public function displayWeb() {
    return array(
      'id' => $this->id,
      'userId' => $this->userId,
      'speciesId' => $this->speciesId,
      'fondVanatoare' => $this->fondVanatoare,
      'nr' => $this->nr,
      'observatii' => $this->observatii,
      'alteSpecii' => $this->alteSpecii,
      'stareaTimpului' => $this->stareaTimpului,
      'latitude' => $this->latitude,
      'longitude' => $this->longitude,
      'altitude' => $this->altitude,
      'detalii' => $this->detalii,
      'versionCode' => $this->versionCode,
      'created' => is_string($this->created) ? $this->created : $this->created->format('Y-m-j H:i:s'),
      'updated' => is_string($this->updated) ? $this->updated : $this->updated->format('Y-m-j H:i:s'),
      'dataInregistrare' => is_string($this->dataInregistrare) ? $this->dataInregistrare : $this->dataInregistrare->format('Y-m-j H:i:s'),
      'files' => $this->files
    );
  }
}