<?php
namespace mapping;

class CommunicationPreference extends BaseMapping {
  
  protected static $routeName = "GET-CommunicationPreference";
  
  public static function fromEntity($entity) {
    $cp = new CommunicationPreference();
    $cp->id = $entity->getId();
    $cp->userRef = User::getRef(array('userid' => $entity->getUser()->getId()));
    $cp->preferenceType = $entity->getPreferenceType();
    $cp->handle = $entity->getHandle();
    $cp->isActive = $entity->getIsActive() == 1;
    return $cp;
  }
}


?>