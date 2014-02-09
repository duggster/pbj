<?php 
namespace mapping;

class Event extends BaseMapping {
  
  protected static $routeName = "GET-Event";
  
  public static function fromEntity($entity) {
    $e = new Event();
    $e->id = $entity->getId();
    $e->title = $entity->getTitle();
    $e->eventDate = $entity->getEventDate();
    $e->eventTime = $entity->getEventTime();
    $e->htmlDescription = $entity->getHtmlDescription();
    
    $e->guestsRef = self::getRefByRoute('GET-EventGuestList', array("eventid" => $e->id));
    
    return $e;
  }
  
  public static function toEntity($m) {
    $e = new \entity\Event();
    if (isset($m->id)) {
      $e->setId($m->id);
    }
    $e->setTitle($m->title);
    $e->setEventDate($m->eventDate);
    $e->setEventTime($m->eventTime);
    $e->setHtmlDescription($m->htmlDescription);
    
    //not handling guests list at this point
    
    return $e;
  }
}


?>