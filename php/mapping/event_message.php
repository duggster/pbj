<?php 
namespace mapping;

class EventMessage extends BaseMapping {
  
  protected static $routeName = "GET-EventMessage";
  
  public static function fromEntity($entity) {
    $e = new \pbj\model\v1_0\EventMessage();
    $e->id = $entity->getId();
    $e->eventid = $entity->getEvent()->getId();
    $e->eventRef = Event::getRef(array('eventid' => $e->eventid));
    if ($entity->getUser() != null) {
      $e->userid = $entity->getUser()->getId();
      $e->userRef = User::getRef(array('userid' => $e->userid));
      $e->userName = $entity->getUser()->getName();
    }
    else {
      $e->userid = null;
      $e->userRef = null;
      $e->userName = "";
    }
    if ($entity->getParentMessage() != NULL) {
      $e->parentid = $entity->getParentMessage()->getId();
      $e->parentMessageRef = EventMessage::getRef(array('eventMessageId' => $e->parentid));
    }
    $e->messageTimestamp = $entity->getMessageTimestamp();
    if ($entity->getMessageTimestamp() != null) {
      $e->messageTimestampFormatted = $entity->getMessageTimestamp()->format('g:i:sa D M j, Y');
    }
    else {
      $e->messageTimestampFormatted = "";
    }
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