<?php
namespace pbj\model\v1_0;

class Guest extends BaseModel {
  public $id;
  public $userid;
  public $name;
  public $communicationHandle;
  public $status;
  public $isOrganizer;
  public $notifyPref;
  public $comments;
  public $kidStatus;
  public $guestLinkId;
  
  public $eventRef;
  public $eventid;
}

?>