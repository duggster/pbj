<?php 
namespace pbj\model\v1_0;

class Event extends BaseModel {
  public $id;
  public $title;
  public $when;
  public $where;
  public $isShowInfo;
  public $isPublished;
  public $htmlDescription;
  public $isOrganizer;
  public $emailAddress;
  
  public $guestsRef;
  public $eventMetadata;
  
}

class EventMetadata extends BaseModel {
  public $guestsTotal;
  public $guestsIn;
  public $guestsOut;
  public $guestsPending;
  public $guestsDraft;
}

class GuestCounts extends BaseModel {
  public $adults = 0;
  public $kids = 0;
  public $babies = 0;
  public $total = 0;
}


?>