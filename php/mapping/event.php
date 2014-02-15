<?php 
namespace mapping;

class Event extends BaseMapping {
  
  protected static $routeName = "GET-Event";
  
  public static function fromEntity($entity) {
    $e = new \pbj\model\v1_0\Event();
    $e->id = $entity->getId();
    $e->title = $entity->getTitle();
    $e->when = $entity->getEventWhen();
    $e->where = $entity->getEventWhere();
    $e->isShowInfo = $entity->getIsShowInfo() == 1;
    $e->htmlDescription = $entity->getHtmlDescription();
    $e->emailAddress = $entity->getEmailAddress();
    
    $e->guestsRef = self::getRefByRoute('GET-EventGuestList', array("eventid" => $e->id));
    $e->eventMetadata = EventMetadata::fromEntity($entity);
    
    return $e;
  }
  
  public static function toEntity($m) {
    $e = new \entity\Event();
    if (isset($m->id)) {
      $e->setId($m->id);
    }
    $e->setTitle($m->title);
    $e->setEventWhen($m->when);
    $e->setEventWhere($m->where);
    $e->setIsShowInfo(($m->isShowInfo)?1:0);
    $e->setHtmlDescription($m->htmlDescription);
    $e->setEmailAddress($m->emailAddress);
    
    //not handling guests list at this point
    
    return $e;
  }
}

class EventMetadata extends BaseMapping {
  public static function fromEntity($eventEntity) {
    $m = null;
    if ($eventEntity != null && $eventEntity->getGuests() != null) {
      $guests = $eventEntity->getGuests();
      
      $m = new \pbj\model\v1_0\EventMetadata();
      $m->guestsTotal = count($guests);
      $m->guestsIn = new \pbj\model\v1_0\GuestCounts();
      $m->guestsOut = new \pbj\model\v1_0\GuestCounts();
      $m->guestsPending = new \pbj\model\v1_0\GuestCounts();
      $m->guestsDraft = new \pbj\model\v1_0\GuestCounts();
      
      foreach ($guests as $g) {
        if ($g->getStatus() == "in") {
          $c = $m->guestsIn;
        }
        else if ($g->getStatus() == "out") {
          $c = $m->guestsOut;
        }
        else if ($g->getStatus() == "invited") {
          $c = $m->guestsPending;
        }
        else {
          $c = $m->guestsDraft;
        }
        
        $c->total++;
        if ($g->getUser()->getKidStatus() == "adult") {
          $c->adults++;
        }
        else if ($g->getUser()->getKidStatus() == "kid") {
          $c->kids++;
        }
        else if ($g->getUser()->getKidStatus() == "baby") {
          $c->babies++;
        }
      }
    }
    return $m;
  }
  
  public static function toEntity($m) {
    //No-op;
  }
}


?>