<?php

$slim->get('/roles', function() {
  $roles = array();
  
  global $slim;
  $params = $slim->request()->get();
  $eventid = NULL;
  $userid = NULL;
  foreach ($params as $name=>$val) {
    if ($name == 'eventid') {
      $eventid = $val;
    }
    if ($name == 'userid') {
      $userid = $val;
    }
  }
  
  $roles = getRoles($eventid, $userid);
  
  echo json_encode($roles);
});

function getRoles($eventid, $userid) {
  $roles = array();
  if ($eventid != NULL && $userid != NULL) {
    
    $em = \getEntityManager();
    $guests = $em->createQuery("SELECT g FROM \entity\Guest g JOIN g.user u JOIN g.event e WHERE u.id = $userid AND e.id = $eventid")->getResult();
    $guest = NULL;
    if (count($guests) == 1) {
      $guest = $guests[0];
    }
    
    if ($guest != NULL) {
      $roles[] = "Guest";
    }
    
    if ($guest != NULL && $guest->getIsOrganizer() == 1) {
      $roles[] = "Organizer";
    }
    
  }
  return $roles;
}

?>