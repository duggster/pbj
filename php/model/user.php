<?php
namespace pbj\model\v1_0;

class User extends BaseModel {
  
  public $id;
  public $name;
  public $isActive;
  public $googleId;
  public $kidStatus;
  
  public $userFamily;
  public $communicationPreferencesRef;
}


?>