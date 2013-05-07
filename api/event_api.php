<?php

use pbj\model\v1_0 as model;

$slim->get('/events/:eventid', function ($eventid) {
  if (authorizeEvent($eventid)) {
    echo json_encode(getEventById($eventid));
  }
})->name('GET-Event');

$slim->put('/events/:eventid', function ($eventid) {
  if (authorizeOrganizer($eventid)) {
    global $slim;
    $body = $slim->request()->getBody();
    $model = model\Event::createFromJSON($body);
    $event = mapping\Event::toEntity($model);
    $em = \getEntityManager();
    $entity = $em->merge($event);
    $em->flush();
    
    echo json_encode(getEventById($eventid));
  }
})->name('PUT-Event');

$slim->post('/events', function () {
  $em = \getEntityManager();
  $em->transactional(function($em) {
    global $slim;
    $body = $slim->request()->getBody();
    $model = model\Event::createFromJSON($body);
    $event = mapping\Event::toEntity($model);
    
    //Add default web modules
    $modules = $em->createQuery('SELECT m FROM entity\WebModule m WHERE m.isEventDefault = 1')->getResult();
    $event->setWebModules($modules);
    $em->persist($event);
    $em->flush();
    
    //Add the logged in user as a guest/organizer
    $guest = new \entity\Guest();
    $guest->setIsOrganizer(1);
    $guest->setStatus("in");
    
    global $sessionManager;
    $userid = $sessionManager->getUserId();
    $user = $em->getReference('\entity\User', $userid);
    $guest->setUser($user);
    $guest->setEvent($event);
    $em->persist($guest);
    $em->flush();
    
    echo json_encode(getEventById($event->getId()));
  });
})->name('POST-Event');

$slim->post('/events/:eventid/copy', function($eventid) {
  if (authorizeEvent($eventid)) {
    $em = \getEntityManager();
    $srcevent = $em->find('\entity\Event', $eventid);
    if ($srcevent != NULL) {
      $em->transactional(function($em) use ($srcevent) {
        global $sessionManager;
        
        //Event Main Info
        $event = new \entity\Event();
        $event->setTitle($srcevent->getTitle());
        $event->setEventDate($srcevent->getEventDate());
        $event->setEventTime($srcevent->getEventTime());
        $event->setIsPublished(0);
        $event->setHtmlDescription($srcevent->getHtmlDescription());
        
        //Web Modules
        $srcmodules = $em->createQuery('SELECT w FROM entity\Event e JOIN entity\WebModule w WHERE e.id = ' . $srcevent->getId())->getResult();
        $event->setWebModules($srcmodules);
        
        $em->persist($event);
        $em->flush();
        
        //Guests
        $userid = $sessionManager->getUserId();
        $srcguests = $srcevent->getGuests();
        foreach($srcguests as $srcguest) {
          $guest = new \entity\Guest();
          $guest->setEvent($event);
          $guest->setUser($srcguest->getUser());
          $guest->setStatus(NULL);
          if ($guest->getUser()->getId() == $userid) {
            $guest->setIsOrganizer(1);
            $guest->setStatus("in");
          }
          else {
            $guest->setIsOrganizer(0);
          }
          $em->persist($guest);
          $em->flush();
        }
        
        echo json_encode(getEventById($event->getId()));
      });
    }
  }
})->name('POST-EventCopy');

$slim->get('/events', function() {
  $es = array();
  $em = \getEntityManager();
  
  global $sessionManager;
  $userId = $sessionManager->getUserId();
  $query = $em->createQuery("SELECT e FROM entity\Event e JOIN e.guests g JOIN g.user u WHERE u.id = $userId AND g.status IS NOT NULL");
  $es = $query->getResult();
  
  $events = array();
  foreach($es as $e) {
    $event = mapping\Event::fromEntity($e);
    $events[] = $event;
  }
  
  echo json_encode($events);
})->name('GET-Events');

$slim->get('/guests/:guestid', function($guestid) {
  $guest = getGuestById($guestid);
  
  echo json_encode($guest);
})->name('GET-Guest');

function getGuestById($guestid) {
  $em = \getEntityManager();
  $g = $em->find('entity\Guest', $guestid);
  $guest = mapping\Guest::fromEntity($g);
  return $guest;
}

$slim->get('/events/:eventid/guests', function($eventid) {
  $guests = array();
  if (authorizeEvent($eventid)) {
    $em = \getEntityManager();
    
    $event = NULL;
    global $slim;
    $params = $slim->request()->get();
    $currentGuest = getLoggedInGuest($eventid);
    $isOrganizer = $currentGuest->getIsOrganizer() == 1;
    
    if (isset($params["isOrganizer"])) {
      $isOrganizer = (($params["isOrganizer"] == "true")? 1 : 0);
      $query = $em->createQuery("SELECT e, g FROM \entity\Event e LEFT JOIN e.guests g WITH g.isOrganizer = $isOrganizer WHERE e.id = $eventid");
      $event = $query->getSingleResult();
    }
    else {
      $event = $em->find('\entity\Event', $eventid);
    }
    
    if ($event != NULL && $event->getGuests() != NULL) {
      $gs = $event->getGuests();
      foreach($gs as $g) {
        if (!$isOrganizer && ($g->getStatus() == "" || $g->getStatus() == NULL)) {
          continue;
        }
        $guests[] = mapping\Guest::fromEntity($g);
      }
    }
  }
  echo json_encode($guests);
})->name('GET-EventGuestList');

$slim->post('/events/:eventid/guests/handles', function($eventid) {
  global $slim;
  $body = $slim->request()->getBody();
  $obj = json_decode($body);
  $handles = $obj->handles;
  
  //Fix copy-paste encoding issues, hopefully
  $enc = mb_detect_encoding($handles);
  $handles = iconv($enc, "ASCII//TRANSLIT", $handles);
  
  $handles = explode(',', $handles);
  
  
  if (authorizeOrganizer($eventid)) {
    $guests = array();
    if ($handles != NULL && sizeof($handles) > 0) {
      foreach($handles as $handle) {
        preg_match('/(?P<name>.*)<(?P<email>.*@.*)>/', $handle, $matches);
        if (isset($matches['email']) && $matches['email'] != NULL) {
          $email = $matches['email'];
          $email = str_replace("<", "", $email);
          $email = str_replace(">", "", $email);
          $email = trim($email);
          $name = trim($matches['name']);
        }
        else {
          $email = trim($handle);
          $name = $email;
        }
        $matches = NULL;
        $newguest = addGuestByHandle($name, $email, $eventid);
        if ($newguest != NULL) {
          $guests[] = getGuestById($newguest->getId());
        }
      }
    }
    
    echo json_encode($guests);
  }
})->name('POST-Guest');

//first lookup the communicationHandle and see if it exists. If so, use that user
//if not, add a new user with the handle as their name, and then add the communicationHandle
//associate the user to the event as a guest
function addGuestByHandle($name, $handle, $eventid) {
  $em = \getEntityManager();  
  $guest = new entity\Guest();
  $user = $em->createQuery("SELECT u FROM entity\User u JOIN u.communicationPreferences p WHERE p.handle = '$handle'")->getOneOrNullResult();
  if ($user == NULL) {
    $user = new \entity\User();
    $user->setName($name);
    $user->setIsActive(1);
    
    $cp = new \entity\CommunicationPreference();
    $cp->setUser($user);
    $cp->setPreferenceType("email");
    $cp->setHandle($handle);
    $cp->setIsActive(1);
    $cp->setIsPrimary(1);
    
    $cps = Array();
    $cps[] = $cp;
    $user->setCommunicationPreferences($cps);
    
    $em->persist($user);
    $em->flush();
  }
  else {
    $userid = $user->getId();
    //TODO: check for existing Guest
    $existingGuest = $em->createQuery("SELECT g FROM entity\Guest g JOIN g.event e JOIN g.user u WHERE e.id = $eventid AND u.id = $userid")->getResult();
    if ($existingGuest != NULL) {
      return NULL;
    }
    $user = $em->getReference('\entity\User', $userid);
  }
  $guest->setUser($user);
  
  $guest->setEvent($em->getReference('\entity\Event', $eventid));
  
  $em->persist($guest);
  $em->flush();
  return $guest;
}

$slim->post('/events/:eventid/guests/sendinvites', function($eventid) {
  global $slim;
  $body = $slim->request()->getBody();
  $obj = json_decode($body);
  $guestids = $obj->guestids;
  $guestids = explode(",", $guestids);
  if (authorizeOrganizer($eventid)) {
    if ($guestids != NULL && sizeof($guestids) > 0) {
      $em = \getEntityManager();
      foreach($guestids as $guestid) {
        $guestid = intval(trim($guestid));
        $guest = $em->find('\entity\Guest', $guestid);
        if ($guest != null && $guest->getEvent()->getId() == $eventid) {
          if ($guest->getStatus() == NULL) {
            $guest->setStatus("invited");
            $em->merge($guest);
            $em->flush();
          }
        }
      }
    }
  }
})->name('POST-sendinvites');

$slim->put('/events/:eventid/guests', function($eventid) {
  global $slim;
  $em = \getEntityManager();
  if (authorizeOrganizer($eventid)) {    
    $body = $slim->request()->getBody();
    $objs = json_decode($body);
    foreach($objs as $obj) {
      $model = model\Guest::createFromAnonObject($obj);
      $guest = mapping\Guest::toEntity($model);
      $guest->setEvent($em->getReference('\entity\Event', $model->eventid));
      $guest->setUser($em->getReference('\entity\User', $model->userid));
      $entity = $em->merge($guest);
      $em->flush();
    }
  }
})->name('PUT-guests');

$slim->delete('/events/:eventid/guests/:guestids', function($eventid, $guestids) {
  $guestids = explode(",", $guestids);
  deleteGuests($guestids);
})->name('DELETE-guests');

$slim->put('/guests/:guestid', function($guestid) {
  global $slim;
  $em = \getEntityManager();
  $body = $slim->request()->getBody();
  $model = model\Guest::createFromJSON($body);
  if (authorizeEvent($model->eventid)) {
    $loggedInGuest = getLoggedInGuest($model->eventid);
    $guest = mapping\Guest::toEntity($model);
    if ($loggedInGuest->getId() == $guest->getId()) {
      $guest->setEvent($em->getReference('\entity\Event', $model->eventid));
      $guest->setUser($em->getReference('\entity\User', $model->userid));
      $entity = $em->merge($guest);
      $em->flush();
    }
    else {
      global $slim;
      $slim->response()->status(403);
    }
    echo json_encode(getGuestById($guestid));
  }
})->name("PUT-Guest");

$slim->delete('/guests/:guestid', function($guestid) {
  $guestids = array();
  $guestids[] = $guestid;
  deleteGuests($guestids);
});

function deleteGuests($guestids) {
  $em = \getEntityManager();
  foreach ($guestids as $guestid) {
    $guest = $em->find('\entity\Guest', $guestid);
    if ($guest != NULL && authorizeOrganizer($guest->getEvent()->getId())) {
      $userid = $guest->getUser()->getId();
      $guests = $em->createQuery("SELECT COUNT(g.id) FROM \entity\Guest g JOIN g.user u WHERE u.id = $userid")->getSingleScalarResult();
      if ($guests == 1) {
        //If this is the only guest, go ahead and remove the user too.
        //The guest database table has a constraint for CASCADE ON DELETE
        $em->remove($guest->getUser());
      }
      else {
        //Remove the guest but leave the user
        $em->remove($guest);
      }
      $em->flush();
    }
  }
}

$slim->get('/eventMessages', function() {
  $ms = array();
  
  global $slim;
  $em = \getEntityManager();
  
  $params = $slim->request()->get();
  $ms = $em->getRepository('entity\EventMessage')->findBy($params);
  
  $messages = array();
  foreach($ms as $m) {
    $message = mapping\EventMessage::fromEntity($m);
    $messages[] = $message;
  }
  
  echo json_encode($messages);
})->name('GET-EventMessages');

$slim->get('/eventMessages/:eventMessageId', function($eventMessageId) {
  echo json_encode(getEventMessageById($eventMessageId));
})->name('GET-EventMessage');

function getEventMessageById($eventMessageId) {
  $em = \getEntityManager();
  $m = $em->find('entity\EventMessage', $eventMessageId);
  $message = mapping\EventMessage::fromEntity($m);
  return $message;
}

$slim->post('/eventMessages', function() {
  global $slim;
  $body = $slim->request()->getBody();
  $model = model\EventMessage::createFromJSON($body);
  if (authorizeEvent($model->eventid)) {
    $message = mapping\EventMessage::toEntity($model);
    $em = \getEntityManager();
    $message->setUser($em->getReference('\entity\User', $model->userid));
    $message->setEvent($em->getReference('\entity\Event', $model->eventid));
    if ($model->parentid != NULL) {
      $message->setParentMessage($em->getReference('\entity\EventMessage', $model->parentid));
    }
    $em->persist($message);
    $em->flush();
    
    echo json_encode(getEventMessageById($message->getId()));
  }
})->name('POST-EventMessage');

$slim->get('/events/:eventid/modules', function($eventid) {
  $modules = array();
  $em = \getEntityManager();
  $event = $em->find('\entity\Event', $eventid);
  if ($event != NULL && $event->getWebModules() != NULL) {
    $ms = $event->getWebModules();
    $guestRoles = getLoggedInGuestRoles($event->getId());
    foreach($ms as $m) {
      $moduleRoles = $m->getWebModuleRoles();
      if (isModuleAllowed($m, $moduleRoles, $guestRoles)) {
        $modules[] = mapping\WebModule::fromEntity($m);
      }
    }
  }
  
  echo json_encode($modules);
})->name('GET-WebModule');

$slim->get('/modules', function() {
  $modules = array();
  global $slim;
  $em = \getEntityManager();
  $params = $slim->request()->get();
  $ms = $em->getRepository('entity\WebModule')->findBy($params);
  if ($ms != NULL) {
    foreach($ms as $m) {
      $modules[] = mapping\WebModule::fromEntity($m);
    }
  }
  
  echo json_encode($modules);
});

function isModuleAllowed($webModule, $moduleRoles, $guestRoles) {
  $moduleId = $webModule->getId();
  foreach($moduleRoles as $moduleRole) {
    $moduleRoleId = $moduleRole->getWebModule()->getId();
    foreach($guestRoles as $guestRole) {
      if ($moduleId == $moduleRoleId 
          && $moduleRole->getRole() == $guestRole 
          && $moduleRole->getAction() == "use") {
        return true;
      }
    }
  }
  return false;
}

?>