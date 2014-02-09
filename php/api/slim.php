<?php
require_once '../env/env.php';

require_once '../vendor/autoload.php';
require_once '../entity/doctrine.php';
require_once '../model/models.php';
require_once '../mapping/mappings.php';
require_once 'SessionManager.php';
require_once 'AuthMiddleware.php';
require_once 'RestRequest.php';

$slim = new \Slim\Slim(array(
    'mode' => 'development',
    'debug' => $ISDEBUG
));

$sessionManager = new SessionManager();

//Checks for a PHP session for every request
$slim->add(new AuthMiddleware());

function getEventById($eventid) {
  $event = NULL;
  $em = \getEntityManager();
  global $sessionManager;
  $userId = $sessionManager->getUserId();
  $e = $em->createQuery("SELECT e FROM entity\Event e JOIN e.guests g JOIN g.user u WHERE e.id = $eventid AND u.id = $userId AND g.status IS NOT NULL")->getOneOrNullResult();
  if ($e != NULL) {
    $event = mapping\Event::fromEntity($e);
    $event->isOrganizer = isOrganizer($eventid);
  }
  return $event;
}

function authorizeEvent($eventid) {
  $authorized = getEventById($eventid) != NULL;
  if (!$authorized) {
    global $slim;
    $slim->response()->status(403);
  }
  return $authorized;
}

function isOrganizer($eventid) {
  $organizer = false;
  $roles = getLoggedInGuestRoles($eventid);
  foreach($roles as $role) {
    if ($role == "organizers") {
      $organizer = true;
      break;
    }
  }
  return $organizer;
}

function authorizeOrganizer($eventid) {
  $authorized = true;
  if (!authorizeEvent($eventid) || !isOrganizer($eventid)) {
    $authorized = false;
    global $slim;
    $slim->response()->status(403);
  }
  return $authorized;
}

function getLoggedInGuest($eventid) {
  global $sessionManager;
  $userid = $sessionManager->getUserId();
  $em = \getEntityManager();
  $guest = $em->createQuery("SELECT g FROM entity\Guest g JOIN g.user u JOIN g.event e WHERE u.id = $userid AND e.id = $eventid")->getOneOrNullResult();
  return $guest;
}

function getLoggedInGuestRoles($eventid) {
  $roles = array();
  $guest = getLoggedInGuest($eventid);
  if ($guest != null) {
    $roles[] = "all guests";
    if ($guest->getIsOrganizer() == 1) {
      $roles[] = "organizers";
    }
  }
  return $roles;
}

require_once 'user_api.php';
require_once 'event_api.php';
require_once 'rbac_api.php';

$slim->run();

?>