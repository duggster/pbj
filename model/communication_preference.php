<?php
namespace pbj\model\v1_0;

class CommunicationPreference extends BaseModel {
  
  public $id;
  public $userRef;
  public $preferenceType;
  public $handle;
  public $isActive;
  public $isPrimary;
 
}


?>