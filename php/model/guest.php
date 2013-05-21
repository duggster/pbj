<?php
namespace pbj\model\v1_0;

class Guest extends BaseModel {
  public $id;
  public $userid;
  public $name;
  public $communicationHandle;
  public $status;
  public $isOrganizer;
  
  public $eventRef;
  public $eventid;
}

?>