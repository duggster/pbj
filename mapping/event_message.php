<?php 
namespace mapping;

class EventMessage extends BaseMapping {
  
  protected static $routeName = "GET-EventMessage";
  
  public static function fromEntity($entity) {
    $e = new EventMessage();
    $e->id = $entity->getId();
    $e->eventid = $entity->getEvent()->getId();
    $e->eventRef = Event::getRef(array('eventid' => $e->eventid));
    $e->userid = $entity->getUser()->getId();
    $e->userRef = User::getRef(array('userid' => $e->userid));
    $e->userName = $entity->getUser()->getName();
    if ($entity->getParentMessage() != NULL) {
      $e->parentid = $entity->getParentMessage()->getId();
      $e->parentMessageRef = EventMessage::getRef(array('eventMessageId' => $e->parentid));
    }
    $e->messageTimestamp = $entity->getMessageTimestamp();
    $e->message = $entity->getMessage();
    
    return $e;
  }
  
  public static function toEntity($m) {
    $e = new \entity\EventMessage();
    $e->setId($m->id);
    $e->setMessageTimestamp($m->messageTimestamp);
    $e->setMessage($m->message);
    
    return $e;
  }
}


?>