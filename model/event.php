<?php 
namespace pbj\model\v1_0;

class Event extends BaseModel {
  public $id;
  public $title;
  public $eventDate;
  public $eventTime;
  public $isPublished;
  public $htmlDescription;
  
  public $guestsRef;
  
}


?>