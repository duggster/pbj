<?php
namespace mapping;

class Guest extends BaseMapping {
  
  protected static $routeName = "GET-Guest";
  
  public static function fromEntity($e) {
    $g = new Guest();
    
    $g->id = $e->getId();
    $g->status = $e->getStatus();
    $g->isOrganizer = $e->getIsOrganizer() == 1;
    
    $user = $e->getUser();
    $g->userid = $user->getId();
    $g->name = $user->getName();
    
    $prefs = $user->getCommunicationPreferences();
    if (!empty($prefs)) {
      foreach($prefs as $pref) {
        if ($pref->getIsPrimary() == 1) {
          $g->communicationHandle = $pref->getHandle();
        }
      }
    }
    
    $g->eventid = $e->getEvent()->getId();
    $g->eventRef = Event::getRef(array('eventid' => $g->eventid));

    return $g;
  }
  
  public static function toEntity($m) {
    $g = new \entity\Guest();
    if (isset($m->id)) {
      $g->setId($m->id);
    }
    $u = new \entity\User();
    if (isset($m->userid)) {
      $u->setId($m->userid);
    }
    $e = new \entity\Event();
    if (isset($m->eventid)) {
      $e->setId($m->eventid);
    }
    $u->setName($m->name);
    $g->setUser($u);
    
    $g->setStatus($m->status);
    $g->setIsOrganizer(($m->isOrganizer)?1:0);
    
    return $g;
  }
  
}

?>