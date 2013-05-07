<?php

use pbj\model\v1_0 as model;

$slim->post('/session', function() {
  global $slim, $sessionManager;
  $body = $slim->request()->getBody();
  $userSession = model\UserSession::createFromJSON($body);
  $userSession = createNewUserSession($userSession->googleId);
  $sessionManager->setUserSession($userSession);
  
  echo json_encode($userSession);
})->name('POST-session');

function createNewUserSession($googleId) {
  $em = \getEntityManager();
  $u = $em->getRepository('entity\User')->findBy(array('googleId'=>$googleId));
  if (sizeof($u) > 0) {
    $u = $u[0];
  }
  $userSession->googleId = $googleId;
  $userSession->user = mapping\User::fromEntity($u);
  return $userSession;
}

$slim->get('/session', function() {
  global $sessionManager;
  $userSession = $sessionManager->getUserSession();
  echo json_encode($userSession);
})->name('GET-session');

$slim->delete('/session', function() {
  global $sessionManager;
  $sessionManager->destroySession();
})->name('DELETE-session');

$slim->delete('/session/:sessionid', function($sessionid) {
  global $sessionManager;
  $sessionManager->destroySession();
})->name('DELETE-sessionid');

$slim->get('/users/:userid', function ($userid) {
  $em = \getEntityManager();
  $u = $em->find('entity\User', $userid);
  $user = mapping\User::fromEntity($u);
  
  echo json_encode($user);
})->name('GET-User');

$slim->get('/users', function() {
  $us = array();
  global $slim;
  $em = \getEntityManager();
  
  $params = $slim->request()->get();
  if (isset($params['guestid'])) {
    $query = $em->createQuery("SELECT u FROM entity\User u JOIN entity\Guest g WHERE g.id = $val AND g.user=u");
    $us = $query->getResult();
  }
  else {
    $us = $em->getRepository('entity\User')->findBy($params);
  }
  
  $users = array();
  foreach($us as $u) {
    $user = mapping\User::fromEntity($u);
    $users[] = $user;
  }
  
  echo json_encode($users);

})->name('GET-Users');

$slim->get('/communicationpreferences/:communicationpreferenceid', function($communicationpreferenceid) {
  $em = \getEntityManager();
  $cp = $em->find('entity\CommunicationPreference', $communicationpreferenceid);
  $pref = mapping\CommunicationPreference::fromEntity($cp);
  
  echo json_encode($pref);
})->name('GET-CommunicationPreference');

?>