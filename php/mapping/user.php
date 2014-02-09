<?php
namespace mapping;

class User extends BaseMapping {
  
  protected static $routeName = "GET-User";
  
  public static function fromEntity($entity) {
    $u = new User();
    $u->id = $entity->getId();
    $u->name = $entity->getName();
    $u->isActive = $entity->getIsActive() == 1;
    $u->googleId = $entity->getGoogleId();
    
    $u->communicationPreferencesRef = array();
    $prefs = $entity->getCommunicationPreferences();
    if (!empty($prefs)) {
      foreach($prefs as $p) {
        $u->communicationPreferencesRef[] = CommunicationPreference::getRef(array('communicationpreferenceid' => $p->getId()));
      }
    }
    
    return $u;
  }
  
  public static function toEntity($m) {
    $e = new \entity\User();
    if (isset($m->id)) {
      $e->setId($m->id);
    }
    $e->setName($m->name);
    $e->setIsActive($m->isActive);
    $e->setGoogleId($m->googleId);
    
    //not handling communication preferences list at this point
    
    return $e;
  }
}


?>