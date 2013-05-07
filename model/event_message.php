<?php 
namespace pbj\model\v1_0;

class EventMessage extends BaseModel {
  public $id;
  public $eventRef;
  public $eventid;
  public $userRef;
  public $userid;
  public $parentMessageRef;
  public $parentid;
  public $messageTimestamp;
  public $message;
}


?>