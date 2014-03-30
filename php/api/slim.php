<?php
require_once '../env/env.php';

require_once '../vendor/autoload.php';
require_once '../entity/doctrine.php';
require_once '../model/models.php';
require_once '../mapping/mappings.php';
require_once 'SessionManager.php';
require_once 'AuthMiddleware.php';
require_once 'RestRequest.php';
require_once 'html2text.php';
require_once 'email/Recipient.php';
require_once 'email/BaseMessage.php';

global $LOG_CONFIG;
Logger::configure("../env/$LOG_CONFIG");
$log = Logger::getLogger('slim');

$slim = new \Slim\Slim(array(
    'mode' => 'development', //this is a custom string, can be anything
    'debug' => false, //this only controls the error() handler. If true, Slim's error page is displayed. If false, custom function is used.
    'log.enabled' => true
));

$sessionManager = new SessionManager();

//Checks for a PHP session for every request
$slim->add(new AuthMiddleware());

$slim->error(function(\Exception $e) use ($slim) {
  global $log;
  $slim->response()->status(500);
  $err = null;
  try {
    if ($slim->getMode() == 'development') {
      $err = \pbj\model\v1_0\ServiceError::createFromException($e);
    }
    else {
      $err = new \pbj\model\v1_0\ServiceError();
    }
    $err->message = "The service encountered an unknown exception.";
    
    //TODO create custom logger to file/email/db
    $slim->getLog()->error($err);
    $log->error($err,$e);
    
  } catch(Exception $ex) {
    $log->error("Error encountered in slim error handler.", $ex);
  }
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($err);
});

$slim->notfound(function() use ($slim) {
  $slim->response()->status(404);
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  //echo json_encode($msg);
});

function isNullOrEmpty($s) {
  return (!isset($s) || trim($s)==='');
}

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
    $slim->halt(403, 'You are not authorized to access this event.');
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
    $slim->halt(403, 'You are not authorized to perform this function for the given event.');
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

function isGuestUpdateAuthorized($guest, $eventid) {
  $isAuthorized = false;
  $eventAuthorized = getEventById($eventid) != NULL;
  if ($eventAuthorized) {
    $loggedInGuest = getLoggedInGuest($eventid);
    
    if (isOrganizer($eventid)) {
      $isAuthorized = true;
    }
    else if ($guest->getId() == $loggedInGuest->getId()) {
      $isAuthorized = true;
    }
    else {
      if ($loggedInGuest->getGuestLink() != null && $guest->getGuestLink() != null) {
        if ($loggedInGuest->getGuestLink()->getId() == $guest->getGuestLink()->getId()) {
          $isAuthorized = true;
        }
      }
    }
  }
  return $isAuthorized;
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