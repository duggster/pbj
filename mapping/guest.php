<?php
namespace mapping;

class Guest extends BaseMapping {
  
  protected static $routeName = "GET-Guest";
  
  public static function fromEntity($e) {
    $g = new \pbj\model\v1_0\Guest();
    
    $g->id = $e->getId();
    $g->status = $e->getStatus();
    $g->isOrganizer = $e->getIsOrganizer() == 1;
    $g->notifyPref = $e->getNotifyPref();
    $g->comments = $e->getComments();
    
    $user = $e->getUser();
    $g->userid = $user->getId();
    $g->name = $user->getName();
    $g->kidStatus = $user->getKidStatus();
    
    $prefs = $user->getCommunicationPreferences();
    if (!empty($prefs)) {
      foreach($prefs as $pref) {
        if ($pref->getIsPrimary() == 1) {
          $g->communicationHandle = $pref->getHandle();
        }
      }
    }
    
    $g->guestLinkId = null;
    if ($e->getGuestLink() != null) {
      $g->guestLinkId = $e->getGuestLink()->getId();
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
      $u->setKidStatus($m->kidStatus);
    }
    $e = new \entity\Event();
    if (isset($m->eventid)) {
      $e->setId($m->eventid);
    }
    $u->setName($m->name);
    $g->setUser($u);
    
    $g->setStatus($m->status);
    $g->setIsOrganizer(($m->isOrganizer)?1:0);
    $g->setNotifyPref($m->notifyPref);
    $g->setComments($m->comments);
    
    return $g;
  }
  
}

?>