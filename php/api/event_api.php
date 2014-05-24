<?php
use pbj\model\v1_0 as model;

//\Doctrine\Common\Util\Debug::dump($entity);

function getEventPage($eventid) {
  global $PBJ_URL;
  return "$PBJ_URL/web/pbj.php#/event/$eventid";
}

$slim->get('/events/:eventid', function ($eventid) {
  if (authorizeEvent($eventid)) {
    global $slim;
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    echo json_encode(getEventById($eventid));
  }
})->name('GET-Event');

$slim->get('/events/:eventid/eventMetadata', function($eventid) {
  if (authorizeEvent($eventid)) {
    global $slim;
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    $event = getEventById($eventid);
    echo json_encode($event->eventMetadata);
  }
})->name('GET-EventMetadata');

$slim->put('/events/:eventid', function ($eventid) {
  if (authorizeOrganizer($eventid)) {
    global $slim;
    $body = $slim->request()->getBody();
    $model = model\Event::createFromJSON($body);
    $event = mapping\Event::toEntity($model);
    $em = \getEntityManager();
    $entity = $em->merge($event);
    $em->flush();
    
    //Create a new Mailgun route if needed
    createMailgunRoute($event);
    
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
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
    
    createMailgunRoute($event);
    
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    echo json_encode(getEventById($event->getId()));
  });
})->name('POST-Event');

$slim->delete('/events/:eventid', function($eventid) {
  if (authorizeOrganizer($eventid)) {
    $em = \getEntityManager();
    $e = $em->find('entity\Event', $eventid);
    
    //Just remove it. The database CASCADE constraints take care of deleting all the children.
    $em->remove($e);
    $em->flush();
  }
})->name('DELETE-Event');

$slim->post('/events/:eventid/copy', function($eventid) {
  if (authorizeEvent($eventid)) {
    $em = \getEntityManager();
    $srcevent = $em->find('\entity\Event', $eventid);
    if ($srcevent != NULL) {
      $em->transactional(function($em) use ($srcevent) {
        global $sessionManager;
        
        //Event Main Info
        $event = new \entity\Event();
        $event->setTitle($srcevent->getTitle()." (copy)");
        $event->setEventWhen("");
        $event->setEventWhere($srcevent->getEventWhere());
        $event->setIsShowInfo($srcevent->getIsShowInfo());
        $event->setIsPublished(0);
        $event->setHtmlDescription($srcevent->getHtmlDescription());
        
        //Web Modules
        //$srcmodules = $em->createQuery('SELECT w FROM entity\WebModule w JOIN entity\Event e WITH e.webModules = w WHERE e.id = ' . $srcevent->getId())->getResult();
        $newmodules = array();
        foreach($srcevent->getWebModules() as $srcmodule) {
          $newmodule = $em->find('entity\WebModule', $srcmodule->getId());
          $newmodules[] = $newmodule;
        }
        $event->setWebModules($newmodules);
        
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
        
        createMailgunRoute($event);
        
        global $slim;
        $resp = $slim->response();
        $resp['Content-Type'] = 'application/json';
        echo json_encode(getEventById($event->getId()));
      });
    }
  }
})->name('POST-EventCopy');

$slim->post('/events/:eventid/broadcast', function($eventid) {
  if (authorizeOrganizer($eventid)) {
    $em = \getEntityManager();
    $event = $em->find('entity\Event', $eventid);
    
    //TODO: will need to handle when a comment can also be added at same time. In that case, 
    //we want message+eventDescription to go in email, but only message in event post.
    $eventMessage = _sendEventMessage($event, 'broadcast-description', null, "", null, getLoggedInGuest($eventid), false);
    global $slim;
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    echo json_encode($eventMessage);
  }
})->name('POST-EventBroadcast');

$slim->post('/mailgun/debug', function() {
  global $slim;
  debugMailgunRequest($slim->request());
});

function debugMailgunRequest($request) {
  global $MAILGUN_OFFLINE_DIR, $log;
  $headers = $request->headers();
  $headers = http_build_query($headers);
  $body = $request->getBody();
  $model = readMailgunBody($request);
  $model_str = var_export($model, true);
  $timestamp = "" . time() . rand(1000,9999);
  $log->debug("Headers: $headers");
  $log->debug("Body: $body");
  $log->debug("Model: $model_str");
  $filecontents = "$headers********$body@@@@@@@@@$model_str";
  file_put_contents("$MAILGUN_OFFLINE_DIR/email-$timestamp.html", $filecontents);
}

function readMailgunBody($request) {
  $body = $request->getBody();
  $arr = array();
  parse_str($body, $arr);
  $obj = (object) $arr;
  $json = json_encode($obj);
  //var_dump(json_decode($json));
  $model = model\Mailgun::createFromJSON($json, true);
  return $model;
}

$slim->post('/mailgun/events', function() {
  //TODO: Verify that request came from Mailgun. See "Securing Webhooks" in documentation
  //TODO: How to verify email is not being spoofed, especially if this can modify an event, post spam messages, add guests, etc.
  
  global $slim, $log;
  $event = null;
  $model = readMailgunBody($slim->request());
  $em = \getEntityManager();
  $em->transactional(function($em) use ($model, $event) {

    //Lookup subject line and sender/recipients to see if existing event 
    //has already been created.
    $eventFound = false;
    $subject = parseSubjectLine($model->Subject);
    $emails = $em->createQuery("SELECT em FROM entity\EventEmail em WHERE em.subject = '$subject' ORDER BY em.timestamp DESC")->getResult();
    if ($emails != null) {
      $recipients = $model->From . ', ' . $model->To . ', ' . $model->Cc;
      $recipients = explode(',', $recipients);
      $addresses = array();
      foreach($recipients as $recipient) {
        $obj = parseEmailAddress($recipient);
        $addresses[] = $obj->email;
      }
      $totaladdresses = count($addresses);
      foreach($emails as $email) {
        $eventid = $email->getEvent()->getId();
        $addressesStr = "'" . implode("','", $addresses) . "'";
        $numguests = $em->createQuery("SELECT COUNT(g) FROM entity\Guest g JOIN g.event e JOIN g.user u JOIN u.communicationPreferences cp WHERE e.id = $eventid AND cp.handle IN ($addressesStr)")->getSingleScalarResult();
        //If more than 50% of the recipients are in this event, then we'll say this email is probably for the same event
        if (($numguests / $totaladdresses) >= 0.5) {
          $obj = parseEmailAddress($model->From);
          $from = $obj->email;
          //Ensure that the person who sent this email is a guest in the event found.
          $fromexists = $em->createQuery("SELECT COUNT(g) FROM entity\Guest g JOIN g.event e JOIN g.user u JOIN u.communicationPreferences cp WHERE e.id = $eventid AND cp.handle = '$from'")->getSingleScalarResult();
          if ($fromexists == 1) {
            $eventFound = true;
            break;
          }
        }
      }
    }
    
    if ($eventFound) {
      _processIncomingEventEmail($model, $eventid);
    }
    else {

      $model->Subject = $subject;
      
      //Create event
      //Event Main Info
      $event = new \entity\Event();
      $event->setTitle($model->Subject);
      $event->setEventWhen("");
      $event->setEventWhere("");
      $event->setIsPublished(1);
      $event->setHtmlDescription($model->strippedHtml);
      
      //Add default web modules
      $modules = $em->createQuery('SELECT m FROM entity\WebModule m WHERE m.isEventDefault = 1')->getResult();
      $event->setWebModules($modules);
      $em->persist($event);
      $em->flush();
      $eventid = $event->getId();
      
      //Add the sender as a guest/organizer
      $organizers = addGuestEmails($model->From, $eventid);
      foreach($organizers as $organizer) {
        $organizer->setIsOrganizer(1);
        $organizer->setStatus("in");
        $em->persist($organizer);
      }
      $em->flush();
      
      //Add the tos and ccs as guests, ignoring any email address for mailgun
      $guestsstring = $model->To . ', ' . $model->Cc;
      global $MAILGUN_SUBDOMAIN;
      $guests = addGuestEmails($guestsstring, $eventid, '/@'.$MAILGUN_SUBDOMAIN.'\.mailgun\.org/');
      
      //update guest status to "invited" since they're on the original email already
      foreach($guests as $guest) {
        if ($guest != null) {
          $guest->setStatus("invited");
          $em->merge($guest);
        }
      }
      $em->flush();
      $em->refresh($event); //refresh to pull in the new guests relationships
      
      createMailgunRoute($event);
      $email = saveEmail($model, $event->getId());
      _sendEventMessage($event, 'replyall-created', null, $email->getSubject(), null, null, false);
      //Must return a 200 (good) or 406 (bad). Any other code will cause Mailgun to retry over period of 8 hours.
    }
  });
});

function createMailgunRoute($event) {
  global $PBJ_URL;
  
  $em = \getEntityManager();
  $eventid = $event->getId();
  $service = "$PBJ_URL/api/slim.php/mailgun/events/$eventid"; //emails sent to $routeName will be routed to this URL
  $existingEventEmail = $event->getEmailAddress();
  $newEventEmail = _getMailgunEmailAddress($event->getTitle(), $event->getId());
  if ($existingEventEmail != $newEventEmail) {
    _createMailgunRoute($newEventEmail, $service);
    $event->setEmailAddress($newEventEmail);
    $em->merge($event);
    $em->flush();
  }
}

function parseSubjectLine($subject) {
  //Parse out Re: and Fw: from subject line
  //http://regexadvice.com/forums/thread/40824.aspx
  //and test cases
  //http://stackoverflow.com/questions/9153629/regex-code-for-removing-fwd-re-etc-from-email-subject
  //the pattern does not catch (Fwd) and leaves end brackets for [Fwd: subject]. Good enough for now?
  $subject = preg_replace('/(?:\[?(?:[Ff][Ww][Dd]?|[Rr][Ee])(?:\s*[:;-]+\s*\]?))/', '', $subject);
  return $subject;
}

//Save the last email received with a unique subject line. (This ensures there is only one unique subject line per event)
function saveEmail($mailgun, $eventid) {
  $email = null;
  $em = \getEntityManager();
  //TODO: Nested transaction didn't seem to work. Hope the calling function always has a transaction open...
  //$em->transactional(function($em) use ($mailgun, $eventid, $email) {
    $email = getSavedEmailBySubject($eventid, $mailgun->Subject);
    if ($email == null) {
      $email = new \entity\EventEmail();
      $event = $em->find('entity\event', $eventid);
      $email->setEvent($event);
      $email->setSubject(parseSubjectLine($mailgun->Subject));
      $em->persist($email);
      $em->flush();
    }
    $email->setFromAddress($mailgun->From);
    $email->setToAddresses($mailgun->To);
    $email->setCcAddresses($mailgun->Cc);
    $email->setBody($mailgun->bodyHtml);
    $email->setStrippedBody($mailgun->strippedHtml);
    $dt = new DateTime();
    $dt->setTimestamp($mailgun->timestamp);
    $email->setTimestamp($dt);
    
    $em->merge($email);
    $em->flush();
  //});
  return $email;
}

function getSavedEmailBySubject($eventid, $subject) {
  $em = \getEntityManager();
  $q = $em->createQuery("SELECT em FROM entity\EventEmail em JOIN em.event ev WHERE ev.id = $eventid AND em.subject = :subject");
  $q->setParameter("subject", parseSubjectLine($subject));
  $email = $q->getOneOrNullResult();
  return $email;
}

$slim->post('/mailgun/events/:eventid', function($eventid) {
  //TODO: Verify that request came from Mailgun. See "Securing Webhooks" in documentation
  //TODO: How to verify email is not being spoofed, especially if this can modify an event, post spam messages, add guests, etc.
  
  global $slim;
  $body = $slim->request()->getBody();
  $mailgun = model\Mailgun::createFromJSON($body, true);
  
  $responseCode = _processIncomingEventEmail($mailgun, $eventid);
});

function _processIncomingEventEmail($mailgun, $eventid) {
  $responseCode = 0;
  $em = \getEntityManager();
  
  //Check that sender is a guest; otherwise do not allow
  $fromObj = parseEmailAddress($mailgun->From);
  $from = $fromObj->email;
  $sender = findGuestByHandle($from, $eventid);
  //TODO: What if sender is not a guest?? Shouldn't action be taken to add them?
  if ($sender != null) {
    $event = $em->find('\entity\Event', $eventid);
    $guests = $event->getGuests();
    
    //Save email for future reference
    $email = saveEmail($mailgun, $eventid);
    
    //Post body as an event message
    $message = $email->getStrippedBody();
    $eventMessage = new model\EventMessage();
    $eventMessage->userid = $sender->getUser()->getId();
    $eventMessage->eventid = $sender->getEvent()->getId();
    $eventMessage->message = $message;
    postEventMessage($eventMessage);
    
    //Process each recipient from original email and categorize/prepare each for later processing.
    //Keep the email thread to only those interested in the event.
    //Remove any guests that have set their status to Out.
    $origRecipients = explode(',', $email->getFromAddress() . ', ' . $email->getToAddresses() . ', ' . $email->getCcAddresses());
    $newGuests = array();
    $existingGuests = array();
    $outGuests = array();
    foreach ($origRecipients as $origRecipient) {
      $obj = parseEmailAddress($origRecipient);
      $address = $obj->email;
      //Don't process PBJ email addresses
      global $MAILGUN_SUBDOMAIN;
      if (preg_match('/@'.$MAILGUN_SUBDOMAIN.'\.mailgun\.org/', $address)) {
        continue;
      }
      //slower approach to query for each email address, but since this is all asynchronous logic it is cleaner and should be ok.
      $guest = findGuestByHandle($address, $eventid);
      $obj = new email\Recipient($obj->name, $obj->email, $origRecipient, $guest);
      
      if ($guest == null) {
        $newGuests[] = $obj;
      }
      else {
        if (!getGuestNotifyPreference($guest)) {
          $outGuests[] = $obj;
        }
        else {
          $guestObj = getRecipientObjsFromGuestArray(array($guest));
          $guestObj = $guestObj[0];
          $obj->name = $guestObj->name;
          $obj->recipient = $guestObj->recipient;
          //$obj->email = $guestObj->email; //replace email with guest's primary email? For now, stay unobtrusive and keep as-is.
          $existingGuests[] = $obj;
        }
      }
    }
    
    //Determine who was left off the original email who should have been on it
    $allGuests = getAllParticipatingGuests($eventid);
    $leftOffGuests = array_reduce($allGuests, function($leftOffGuests, $allGuest) use ($existingGuests) {
      $found = false;
      foreach($existingGuests as $existingGuest) {
        if ($existingGuest->guest->getId() == $allGuest->getId()) {
          $found = true;
          break;
        }
      }
      if (!$found) {
        if (getGuestNotifyPreference($allGuest)) {
          $obj = getRecipientObjsFromGuestArray(array($allGuest));
          $obj = $obj[0];
          $leftOffGuests[] = $obj;
        }
      }
      return $leftOffGuests;
    }, array());
    
    //If other recipients are not already guests, send request to organizers to add them.
    //TODO: give organizer an option to remove them from the thread (in the case where the guest had previousy been 
    //removed from the event). This option will send a replyAll letting others know the address has been removed from the thread, 
    //and a separate email should go to the address letting them know they have been removed.
    if (sizeof($newGuests) > 0) {
      $organizers = getAllOrganizers($eventid);
      //TODO: send email to organizers with non-guests specified. 
      //Organizers can either add them or remove them.
      //Other option is just to ignore non-guests, but if so, organizers shouldn't get an email every single time. Need to remember addresses being ignored?
      //Maybe treat ignored addresses as guests with status="out"?
      //Also ties in with security and how "open" events can be to people not invited by organizer
    }
    
    if (sizeof($outGuests) > 0) {
      foreach($outGuests as $outGuest) {
        sendGuestEmail('guest-dropped', $outGuest->guest, $email->getSubject());
      }
    }
    
    //Construct and send the ReplyAll email if either guests need to be added or removed from the thread.
    //If not, no email is sent.
    if (sizeof($leftOffGuests) > 0 || sizeof($outGuests) > 0) {
      $vars = array("leftOffGuests"=>$leftOffGuests, "outGuests"=>$outGuests);
      _sendEventMessage($event, 'replyall-post', $vars, $email->getSubject(), $newGuests, null, false);
    }
  }
  return $responseCode;
}

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
    $event->isOrganizer = isOrganizer($event->id);
    $events[] = $event;
  }
  
  global $slim;
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($events);
})->name('GET-Events');

$slim->get('/guests/:guestid', function($guestid) {
  $guest = getGuestById($guestid);
  
  global $slim;
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($guest);
})->name('GET-Guest');

function getGuestById($guestid) {
  $em = \getEntityManager();
  $g = $em->find('entity\Guest', $guestid);
  $guest = mapping\Guest::fromEntity($g);
  return $guest;
}

$slim->get('/events/:eventid/guests', function($eventid) {
  global $slim;
  $guests = array();
  if (authorizeEvent($eventid)) {
    $em = \getEntityManager();
    
    $event = NULL;
    $params = $slim->request()->get();
    $isOrganizer = isOrganizer($eventid);
    
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
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($guests);
})->name('GET-EventGuestList');

function findGuestByHandle($handle, $eventid) {
  $em = \getEntityManager();
  return $em->createQuery("SELECT g FROM \entity\Guest g JOIN g.event e JOIN g.user u JOIN u.communicationPreferences cp WHERE cp.handle = '$handle' AND e.id = $eventid")->getOneOrNullResult();
}

function getAllInvitedGuests($eventid) {
  $guests = array();
  $em = \getEntityManager();
  $event = $em->find('\entity\Event', $eventid);
  if ($event != null) {
    $gs = $event->getGuests();
    foreach($gs as $g) {
      if ($g->getStatus() != "" && $g->getStatus() != NULL) {
        $guests[] = $g;
      }
    }
  }
  return $guests;
}

function getAllParticipatingGuests($eventid) {
  $guests = array();
  $em = \getEntityManager();
  $event = $em->find('\entity\Event', $eventid);
  if ($event != null) {
    $gs = $event->getGuests();
    foreach($gs as $g) {
      if ($g->getStatus() == "invited" || $g->getStatus() == "in") {
        $guests[] = $g;
      }
    }
  }
  return $guests;
}

function getAllOrganizers($eventid) {
  $guests = array();
  $em = \getEntityManager();
  $isOrganizer = 1;
  $query = $em->createQuery("SELECT e, g FROM \entity\Event e LEFT JOIN e.guests g WITH g.isOrganizer = $isOrganizer WHERE e.id = $eventid");
  $event = $query->getSingleResult();
  if ($event != null) {
    $gs = $event->getGuests();
    foreach($gs as $g) {
      if ($g->getStatus() != "" && $g->getStatus() != NULL) {
        $guests[] = $g;
      }
    }
  }
  return $guests;
}

$slim->post('/events/:eventid/guests/handles', function($eventid) {
  $guests = array();
  if (authorizeOrganizer($eventid)) {
    global $slim;
    $body = $slim->request()->getBody();
    $obj = json_decode($body);
    $handles = $obj->handles;

    $newguests = addGuestEmails($handles, $eventid);
    
    $newguestsnotify = array_filter($newguests, function($guest) {
      return ($guest->getStatus() != null);
    });
    
    //TODO: broadcastMessage? Currently functionality only allows you to add guest with status null, so no broadcast ncessary
    
    /*
    $names = "";
    foreach($newguests as $guest) {
      if ($guest != NULL) {
        $g = getGuestById($guest->getId());
        $guests[] = $g;
        if ($g->status != null) {
          if (!empty($names)) {
            $names .= ', ';
          }
          $names .= $g->name;
        }
      }
    }
    
    if (!empty($names)) {
      broadcastEmail($eventid, "Guests have been added to the event: $names");
    }
    */
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    echo json_encode($guests);
  }
})->name('POST-Guest');

//Given a list of comma-separated emails and names, parse through each and add as a guest. 
//Creates a new user if one doesn't already exist.
function addGuestEmails($handles, $eventid, $skipEmailPattern=null) {
  $guests = array();
  
  //Fix copy-paste encoding issues, hopefully
  $enc = mb_detect_encoding($handles);
  $handles = iconv($enc, "ASCII//TRANSLIT", $handles);
  
  $handles = explode(',', $handles);
  if ($handles != NULL && sizeof($handles) > 0) {
    foreach($handles as $handle) {
      $obj = parseEmailAddress($handle);
      $name = $obj->name;
      $email = $obj->email;
      if ($email != "" && ($skipEmailPattern == null || !preg_match($skipEmailPattern, $email))) {
        $newguest = addGuestByHandle($name, $email, $eventid);
        $guests[] = $newguest;
      }
    }
  }
  return $guests;
}

function parseEmailAddress($handle) {
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
  $recipient = new email\Recipient($name, $email, "$name <$email>");
  return $recipient;
}

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
    //If user's name is the default (same as email address) but the $name passed in is something different,
    //then update the user with the new $name.
    if ($user->getName() == $handle && $name != $handle) {
      $user->setName($name);
      $em->merge($user);
    }
  }
  $guest->setUser($user);
  
  $event = $em->getReference('\entity\Event', $eventid);
  $guest->setEvent($event);
  
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
      $event = $em->find('\entity\Event', $eventid);
      $eventTitle = $event->getTitle();
      $guests = array();
      foreach($guestids as $guestid) {
        $guestid = intval(trim($guestid));
        $guest = $em->find('\entity\Guest', $guestid);
        if ($guest != null && $guest->getEvent()->getId() == $eventid) {
          if ($guest->getStatus() == NULL) {
            $guest->setStatus("invited");
            $em->merge($guest);
            $em->flush();
            $guests[] = $guest;
            sendGuestEmail('guest-invited', $guest, "Invitation for $eventTitle");
          }
        }
      }
      
      if (count($guests) > 0) {
        $vars["recipients"] = getRecipientObjsFromGuestArray($guests);
        _sendEventMessage($event, 'broadcast-invited', $vars, "", null, getLoggedInGuest($eventid));
      }
    }
  }
})->name('POST-sendinvites');

$slim->post('/events/:eventid/guests/link', function($eventid) {
  global $slim;
  $body = $slim->request()->getBody();
  $obj = json_decode($body);
  $guestids = $obj->guestids;
  $guestids = explode(",", $guestids);
  $loggedInGuest = getLoggedInGuest($eventid);
  $authorized = false;
  foreach ($guestids as $guestid) {
    $authorized = ($guestid == $loggedInGuest->getId());
    if ($authorized) {
      break;
    }
  }
  if ($authorized || authorizeOrganizer($eventid)) {
    $em = \getEntityManager();
    $modifiedGuests = array();
    $em->transactional(function($em) use ($guestids, $eventid, &$modifiedGuests) {
      $guests = array();
      $activelink = null;
      $toomanylinks = false;
      $checkorphans = array();
      foreach ($guestids as $guestid) {
        $guest = $em->find('\entity\Guest', $guestid);
        if ($guest != null) {
          if ($guest->getEvent()->getId() != $eventid) {
            //TODO: throw error instead of quietly skipping
            continue;
          }
          $guestlink = $guest->getGuestLink();
          if ($guestlink != null) {
            if ($activelink != $guestlink && $activelink != null && !$toomanylinks) {
              //Different links already exist in the set - logic to remove any existing links and create a new one. Also have to handle orphans in that case.
              $toomanylinks = true;
              $checkorphans[] = $activelink->getId();
            }
            if ($toomanylinks) {
              if (!in_array($guestlink->getId(), $checkorphans)) {
                //only add a unique link once
                $checkorphans[] = $guestlink->getId();
              }
            }
            $activelink = $guestlink;
          }
          $guests[] = $guest;
        }
      }
      if ($activelink == null || $toomanylinks) {
        $activelink = new entity\GuestLink();
        $em->persist($activelink);
        $em->flush();
      }
      foreach ($guests as $guest) {
        $guest->setGuestLink($activelink);
        $em->merge($guest);
        $modifiedGuests[] = $guest;
      }
      $em->flush();
      foreach ($checkorphans as $guestLinkId) {
        $count = $em->createQuery("SELECT COUNT(g.id) FROM entity\Guest g LEFT JOIN g.guestLink l WHERE l.id = $guestLinkId")->getSingleScalarResult();
        if ($count == 1) {
          $guestLink = $em->find("entity\GuestLink", $guestLinkId);
          $guest = $guestLink->getGuests()->get(0);
          $guest->setGuestLink(null);
          $em->merge($guest);
          $em->remove($guestLink);
          $modifiedGuests[] = $guest;
        }
        $em->flush();
      }
    });
    $models = array();
    foreach ($modifiedGuests as $guest) {
      //This should all be cached and no query to DB should be necessary
      $models[] = getGuestById($guest->getId());
    }
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    echo json_encode($models);
  }
});

$slim->post('/events/:eventid/guests/unlink', function($eventid) {
  global $slim;
  $body = $slim->request()->getBody();
  $obj = json_decode($body);
  $guestids = $obj->guestids;
  $guestids = explode(",", $guestids);
  $loggedInGuest = getLoggedInGuest($eventid);
  $authorized = false;
  foreach ($guestids as $guestid) {
    $authorized = ($guestid == $loggedInGuest->getId());
    if ($authorized) {
      break;
    }
  }
  if ($authorized || authorizeOrganizer($eventid)) {
    $em = \getEntityManager();
    $modifiedGuests = array();
    $em->transactional(function($em) use ($guestids, $eventid, &$modifiedGuests) { 
      $links = array();
      foreach ($guestids as $guestid) {
        $guest = $em->find('\entity\Guest', $guestid);
        if ($guest != null) {
          if ($guest->getEvent()->getId() != $eventid) {
            //TODO: throw error instead of quietly skipping
            continue;
          }
          $link = $guest->getGuestLink();
          if ($link != null) {
            if (!in_array($link->getId(), $links)) {
              $links[] = $link->getId();
            }
            $guest->setGuestLink(null);
            $em->merge($guest);
            $modifiedGuests[] = $guest;
          }
        }
      }
      $em->flush();
      //FIx any orphans and remove the links from the DB if needed
      foreach($links as $guestLinkId) {
        $count = $em->createQuery("SELECT COUNT(g.id) FROM entity\Guest g LEFT JOIN g.guestLink l WHERE l.id = $guestLinkId")->getSingleScalarResult();
        if ($count <= 1) {
          $guestLink = $em->find("entity\GuestLink", $guestLinkId);
          if ($guestLink->getGuests() != null && $guestLink->getGuests()->count() == 1) {
            $guest = $guestLink->getGuests()->get(0);
            $guest->setGuestLink(null);
            $em->merge($guest);
            $modifiedGuests[] = $guest;
          }
          $em->remove($guestLink);
        }
        $em->flush();
      }
    });
    $models = array();
    foreach ($modifiedGuests as $guest) {
      //This should all be cached and no query to DB should be necessary
      $models[] = getGuestById($guest->getId());
    }
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    echo json_encode($models);
  }
});

$slim->put('/events/:eventid/guests', function($eventid) {
  global $slim;
  $em = \getEntityManager();
  $body = $slim->request()->getBody();
  $objs = json_decode($body);
  $event = $em->getReference('\entity\Event', $eventid);
  $guests = array();
  foreach($objs as $obj) {
    $model = model\Guest::createFromAnonObject($obj);
    $guest = mapping\Guest::toEntity($model);
    $guest->setEvent($event);
    $guest->setUser($em->getReference('\entity\User', $model->userid));
    if ($model->guestLinkId != null && !empty($model->guestLinkId)) {
      $guest->setGuestLink($em->getReference('\entity\GuestLink', $model->guestLinkId));
    }
    
    //TODO: return back an error for guests not authorized
    if (isGuestUpdateAuthorized($guest, $eventid)) {
      $guests[] = $guest;
    }
  }
  updateGuests($guests, $event);
  $models = array();
  foreach ($guests as $guest) {
    //This should all be cached and no query to DB should be necessary
    $models[] = getGuestById($guest->getId());
  }
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($models);
})->name('PUT-guests');

$slim->put('/guests/:guestid', function($guestid) {
  global $slim;
  $em = \getEntityManager();
  $body = $slim->request()->getBody();
  $model = model\Guest::createFromJSON($body);
  $guest = mapping\Guest::toEntity($model);
  $guest->setEvent($em->getReference('\entity\Event', $model->eventid));
  $guest->setUser($em->getReference('\entity\User', $model->userid));
  $guest->getUser()->setKidStatus($model->kidStatus);
  if ($model->guestLinkId != null && !empty($model->guestLinkId)) {
    $guest->setGuestLink($em->getReference('\entity\GuestLink', $model->guestLinkId));
  }

  $resp = $slim->response();
  if (isGuestUpdateAuthorized($guest, $model->eventid)) {
    updateGuests(array($guest), $guest->getEvent());
    $resp['Content-Type'] = 'application/json';
    echo json_encode(getGuestById($guestid));
  }
  else {
    $slim->halt(403, 'You are not authorized to update this guest.');
  }
})->name("PUT-Guest");

function updateGuests($guests, $event) {
  $em = \getEntityManager();
  $em->transactional(function($em) use ($guests, $event) {
    $statuschanged = array();
    foreach($guests as $guest) {
      $oldGuest = $em->find('\entity\Guest', $guest->getId());
      $eventTitle = $oldGuest->getEvent()->getTitle();
      $newStatus = $guest->getStatus();
      //TODO: also handle when consumer changes guestLinkId (disallow unless an organizer)
      if ($oldGuest->getStatus() != $newStatus) {
        $statuschanged[] = $guest;
        if ($newStatus == 'out' && $guest->getNotifyPref() != 'in') {
          sendGuestEmail('guest-out', $guest, "$eventTitle Status Change");
        }
        else {
          sendGuestEmail('guest-statuschange', $guest, "$eventTitle Status Change");
        }
      }
      $em->merge($guest);
    }
    $em->flush();
    if (count($statuschanged) > 0) {
      $vars["recipients"] = getRecipientObjsFromGuestArray($statuschanged);
      _sendEventMessage($event, 'broadcast-statuschange', $vars, "", null, getLoggedInGuest($event->getId()));
    }
  });
  return $guests;
}

$slim->get('/guests/:guestid/respond', function($guestid) {
  global $slim;
  $params = $slim->request()->get();
  if (isset($params["status"])) {
    
  }
})->name('GET-respond');

$slim->delete('/events/:eventid/guests/:guestids', function($eventid, $guestids) {
  $guestids = explode(",", $guestids);
  deleteGuests($guestids);
})->name('DELETE-guests');

$slim->delete('/guests/:guestid', function($guestid) {
  $guestids = array();
  $guestids[] = $guestid;
  deleteGuests($guestids);
});

function deleteGuests($guestids) {
  $em = \getEntityManager();
  $em->transactional(function($em) use ($guestids) {
    foreach ($guestids as $guestid) {
      $guest = $em->find('\entity\Guest', $guestid);
      if ($guest != NULL && authorizeOrganizer($guest->getEvent()->getId())) {
        $userid = $guest->getUser()->getId();
        $guests = $em->createQuery("SELECT COUNT(g.id) FROM \entity\Guest g JOIN g.user u WHERE u.id = $userid")->getSingleScalarResult();
        //Remove the guest but leave the user
        $em->remove($guest);
        /*
        //The logic to remove the user is problematic because of previous event messages left by the user.
        if ($guests == 1) {
          //If this is the only guest, go ahead and remove the user too.
          //The guest database table has a constraint for CASCADE ON DELETE
          $em->remove($guest->getUser());
        }
        else {
          //Remove the guest but leave the user
          $em->remove($guest);
        }
        */
        $em->flush();
      }
    }
  });
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
  
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($messages);
})->name('GET-EventMessages');

$slim->get('/eventMessages/:eventMessageId', function($eventMessageId) {
  global $slim;
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
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
    $em = \getEntityManager();
    $event = $em->find('entity\Event', $model->eventid);
    $vars = array();
    $vars["message"] = $model->message;
    $eventMessage = _sendEventMessage($event, 'broadcast-message', $vars, "", null, getLoggedInGuest($model->eventid));
    
    $resp = $slim->response();
    $resp['Content-Type'] = 'application/json';
    echo json_encode($eventMessage);
  }
})->name('POST-EventMessage');

function postEventMessage($model) {
  $message = mapping\EventMessage::toEntity($model);
  if (isNullOrEmpty($message->getMessage())) {
    return null;
  }
  $em = \getEntityManager();
  $message->setEvent($em->getReference('\entity\Event', $model->eventid));
  if ($model->userid != null) {
    $message->setUser($em->getReference('\entity\User', $model->userid));
  }
  if ($model->parentid != NULL) {
    $message->setParentMessage($em->getReference('\entity\EventMessage', $model->parentid));
  }
  $em->persist($message);
  $em->flush();
  $em->refresh($message);
  $message = mapping\EventMessage::fromEntity($message);
  return $message;
}

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
  
  global $slim;
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($modules);
})->name('GET-WebModule');

$slim->post('/events/:eventid/modules/:moduleid', function($eventid, $moduleid) {
  $em = \getEntityManager();
  if (authorizeEvent($eventid)) {
    $event = $em->find('\entity\Event', $eventid);
    if ($event != NULL && $event->getWebModules() != NULL) {
      $module = $em->find('\entity\WebModule', $moduleid);
      $event->getWebModules()->add($module);
      $em->flush();
    }
  }
  
  global $slim;
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
  echo json_encode($module);
})->name('POST-WebModule');

$slim->delete('/events/:eventid/modules/:moduleid', function($eventid, $moduleid) {
  $em = \getEntityManager();
  if (authorizeEvent($eventid)) {
    $event = $em->find('\entity\Event', $eventid);
    if ($event != NULL && $event->getWebModules() != NULL) {
      $module = $em->find('\entity\WebModule', $moduleid);
      if ($module->getIsEventDefault() != 1) {
        $event->getWebModules()->removeElement($module);
        $em->flush();
      }
    }
  }
  //just return a 200 code
})->name('DELETE-WebModule');

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
  
  $resp = $slim->response();
  $resp['Content-Type'] = 'application/json';
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

function getRecipientsStringFromGuestArray($guests, $filterFunction = null) {
  $objs = getRecipientObjsFromGuestArray($guests, $filterFunction);
  $recipients = array_reduce($objs, function($s, $obj) {
    if (!empty($s)) {
      $s .= ', ';
    }
    $s .= $obj->recipient;
    return $s;
  }, '');
  return $recipients;
}

function getRecipientObjsFromGuestArray($guests, $filterFunction = null) {
  $recipients = array();
  if ($guests != null && sizeof($guests) > 0) {
    foreach($guests as $guest) {
      if ($guest->getStatus() != null) {
        if ($filterFunction == null || $filterFunction($guest)) {
          $user = $guest->getUser();
          $comms = $user->getCommunicationPreferences();
          $email = null;
          if ($comms != null && sizeof($comms) > 0) {
            foreach($comms as $comm) {
              if ($comm->getPreferenceType() == 'email'
                  && $comm->getIsActive() == 1
                  && $comm->getIsPrimary() == 1) {
                $email = $comm->getHandle();
              }
            }
          }
          if ($email != null) {
            $name = $user->getName();
            $recipient = $email;
            if ($name != $email) {
              $recipient = "$name <$email>"; 
            }
            $obj = new email\Recipient($name, $email, $recipient, $guest);
            $recipients[] = $obj;
          }
        }
      }
    }
  }
  return $recipients;
}

function getGuestNotifyPreference($guest) {
  $include = (($guest->getStatus() == "in" || $guest->getStatus() == "invited")
              || ($guest->getStatus() == "out" && $guest->getNotifyPref() == "in"));
  return $include;
}

function sendGuestEmail($contentName, $guest, $subject = "", $vars = null) {
  $response = null;
  $to = getRecipientObjsFromGuestArray(array($guest));
  if ($to != null && count($to) > 0) {
    $to = $to[0]->recipient;
    $from = $guest->getEvent()->getEmailAddress();
    
    if ($vars == null) {
      $vars = array();
    }
    $vars["event"] = $guest->getEvent();
    $vars["guest"] = $guest;
    $bodyHtml = email\BaseMessage::getEmailHtmlByName($contentName, $vars);
    $bodyPlain = convert_html_to_text($bodyHtml);
    $response = _sendEmail($from, $to, null, $subject, $bodyHtml, $bodyPlain);
    return $response;
  }
}

class OutgoingEventMessage {
  public $event;
  public $contentName;
  public $vars = null;
  public $subject = "";
  public $addRecipients = null;
  public $fromGuest = null;
}

function _sendEventMessage($event, $contentName, $vars = null, $subject = "", $addRecipients = null, $fromGuest = null, $postToEvent = true, $sendEmail = true) {
  $eventMessage = null;
  $em = \getEntityManager();
  if ($event != null) {
    //Prefer to continue an existing email thread if one exists
    if ($subject == "") {
      $subject = $event->getTitle();
    }
    $email = getSavedEmailBySubject($event->getId(), $subject);
    if ($email == null) {
      $email = new \entity\EventEmail();
      $email->setEvent($event);
      $email->setSubject($subject);
      $email->setBody('');
      $email->setStrippedBody('');
    }
    
    //Adjust subject to remove any Re: string if it exists
    $subject = $email->getSubject();
    if (substr(trim(strtolower($subject)), 0, 3) != 're:') {
      $subject = 'Re: ' . $subject;
    }
    
    //From
    $fromObj = null;
    if ($fromGuest != null) {
      $fromObj = getRecipientObjsFromGuestArray(array($fromGuest));
      $fromObj = $fromObj[0];
      $sender = $event->getEmailAddress();
    }
    else {
      $fromName = $event->getTitle();
      $fromEmail = $event->getEmailAddress();
      $fromObj = new email\Recipient($fromName, $fromEmail, "$fromName <$fromEmail>", null);
      $sender = null;
    }
    
    //To
    $guests = $event->getGuests();
    if ($guests != null && sizeof($guests) > 0) {
      $to = getRecipientObjsFromGuestArray($guests, function($guest) {
        return getGuestNotifyPreference($guest);
      });
      if ($addRecipients != null && count($addRecipients) > 0) {
        $to = $to + $addRecipients;
      }
      //Remove the $from address from the other recipient field
      $to = array_filter($to, function($address) use ($fromObj) {
        return $address->email != $fromObj->email;
      });
    }
      
    //Prepare to post event message and send out email
    $vars["event"] = $event;
    $content = email\BaseMessage::getMessageContentByName($contentName, $vars);
    $bodyHtml = email\BaseMessage::getEmailHtmlWithContent($content, $vars);
    //TODO: How to account for HTML newlines - <br/>, <p>, <div>, etc...
    //$replyBodyHtml = preg_replace("/\n/", "\n>", '>' . $email->getBody());
    $replyBodyHtml = $email->getBody();
    $bodyHtml = "$bodyHtml<br/>\n$replyBodyHtml";
    $bodyPlain = convert_html_to_text($bodyHtml);
    
    //Anything that is broadcast to all should also be posted to event messages      
    $eventMessage = new model\EventMessage();
    //TODO: Should eventMessages handle NULL user by using event name instead? Maybe leave that up to the UI
    if ($fromGuest != null) {
      $eventMessage->userid = $fromGuest->getUser()->getId();
    }
    else {
      $eventMessage->userid = null;
    }
    $eventMessage->eventid = $event->getId();
    $eventMessage->message = $content;
    if ($postToEvent) {
      $eventMessage = postEventMessage($eventMessage);
    }
    
    if (count($to) > 0 && $sendEmail) {
      $tostr = array_reduce($to, function($s, $toObj) {
        if (!empty($s)) {
          $s .= ', ';
        }
        $s .= $toObj->recipient;
        return $s;
      }, '');
      
      //TODO: can add a BCC field for a mailgun address that will save the email message
      //Send the email
      $responseCode = _sendEmail(
        $fromObj->recipient, 
        $tostr, 
        '',
        $subject, 
        $bodyHtml,
        $bodyPlain,
        $sender
      );
    }
  }
  return $eventMessage;
}

//$from should be the person's address, and $sender should be the PBJ address
function _sendEmail($from, $to, $cc, $subject, $bodyHtml, $bodyPlain, $sender = null) {
  global $MAILGUN_URL, $MAILGUN_USERNAME, $MAILGUN_PASSWORD, $MAILGUN_OFFLINE, $MAILGUN_TEST, $MAILGUN_TEST_TO, $MAILGUN_OFFLINE_DIR;
  if (empty($to)) {
    throw new Exception("'To' required");
  }
  if ($MAILGUN_TEST) {
    $message = array(
      'from' => $from,
      'to' => $MAILGUN_TEST_TO,
      'subject' => $subject,
      'html' => "From: $from<br/>To: $to<br/>CC: $cc<br/><br/>$bodyHtml",
      'text' => "To: $to\nCC: $cc\n\n$bodyPlain"
    );
  } else {
    $message = array(
      'from' => $from,
      'to' => $to,
      'subject' => $subject,
      'html' => $bodyHtml,
      'text' => $bodyPlain
    );
    if (!empty($cc)) {
      $message["cc"] = $cc;
    }
    if ($sender != null) {
      $message["h:sender"] = $sender;
      $message["h:reply-to"] = $sender;
    }
  }
  
  $responseCode = 0;
  if (!$MAILGUN_OFFLINE) {
    $request = new RestRequest($MAILGUN_URL, 'POST', 
      array(
        'Content-Type' => 'application/x-www-form-urlencoded'
      ), 
      $message
    );
    $request->setUsername($MAILGUN_USERNAME);
    $request->setPassword($MAILGUN_PASSWORD);
    //var_dump($message);
    //var_dump($request);
    $request->execute();
    $responseInfo = $request->getResponseInfo();
    $responseCode = $responseInfo["http_code"];
    //var_dump($request);
  }
  else {
    $filecontents = "";
    $timestamp = "" . time() . rand(1000,9999);
    $filecontents .= date(DATE_RFC822) . "<br/>" . $message["subject"] . "<br/><br/>" . $message["html"];
    file_put_contents("$MAILGUN_OFFLINE_DIR/email-$timestamp.html", $filecontents);
  }
  return $responseCode;
}

function _createMailgunRoute($emailAddress, $service) {
  global $MAILGUN_ROUTES_URL, $MAILGUN_USERNAME, $MAILGUN_PASSWORD;
  $request = new RestRequest($MAILGUN_ROUTES_URL, 'POST', 
    array(
      'Content-Type' => 'application/x-www-form-urlencoded'
    ), 
    array(
      'priority' => 1,
      'expression' => 'match_recipient("' . $emailAddress . '")',
      'action[1]' => 'forward("' . $service . '")',
      'action[2]' => 'stop()',
      'description' => ''
    )
  );
  $request->setUsername($MAILGUN_USERNAME);
  $request->setPassword($MAILGUN_PASSWORD);
  $request->execute();
  $responseInfo = $request->getResponseInfo();
  $responseCode = $responseInfo["http_code"];
  return $emailAddress;
}

function _getMailgunEmailAddress($eventName, $eventid) {
  global $MAILGUN_SUBDOMAIN;
  $routeName = preg_replace('/\W+/', '', $eventName); //Remove any non-word (alphanumeric) characters
  $routeName = substr($routeName, 0, 20); //truncate to length of 20
  $routeName = $routeName . '-' . $eventid; //add eventid to end to ensure uniqueness
  $emailAddress = "$routeName@$MAILGUN_SUBDOMAIN.mailgun.org";
  return $emailAddress;
}

?>