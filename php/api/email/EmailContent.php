<?php
require_once __DIR__.'/../../env/env.php';
$defined_vars = get_defined_vars();
$ctx = $defined_vars["inst"];
extract($ctx->vars);

//TODO: handle error case when expected to have $recipients array but is null. Try/catch at least.
switch ($ctx->contentName) {
case('guest-invited'):
?>
You have been invited to the event <?php echo $event->getTitle() ?>. 
<br/><br/>
Log in to the PBJ website below in order to see details about the event and let the organizer know if you can make it or not.
<?php
break;
case('broadcast-invited'):
?>
Guests have been invited to the event: <br/>
<?php
foreach ($recipients as $recipient) {
  echo $recipient->recipient . '<br/>';
}
?>
<?php
break;
case('broadcast-description'):
echo $event->getHtmlDescription();
?>
<?php
break;
case('broadcast-statuschange'):
foreach($recipients as $recipient) {
  $name = $recipient->guest->getUser()->getName();
  $status = $recipient->guest->getStatus();
  echo "$name's status changed to '$status'.<br/>";
}
?>
<?php
break;
case('broadcast-message'):
echo $message;
?>
<?php
break;
case('guest-statuschange'):
$status = $guest->getStatus();
echo "Your status has been changed to '$status'.";
?>
<?php
break;
case('guest-out'):
?>
Your status is now set to 'out' and you will not be included on any more discussions about this event.
<br/><br/>
NOTE: Select the "Keep me in the loop" option for this event in order to continue receiving notifications
<?php
break;
case('guest-dropped'):
?>
Just letting you know that you were dropped from the thread because you indicated you're not interested in discussions about this event.
<br/><br/>
If you would like to be included in discussions going forward, select the "Keep me in the loop" option for this event.
<?php
break;
case('broadcast-dropped'):
?>
Removing guests from the email thread who are not participating in the event: <br/>
<?php
foreach ($recipients as $recipient) {
  echo $recipient->recipient . '<br/>';
}
?>
<?php
break;
case('broadcast-added'):
?>
Adding other guests to the email thread to keep everyone in the loop: <br/>
<?php
foreach ($recipients as $recipient) {
  echo $recipient->recipient . '<br/>';
}
?>
<?php
break;
case('replyall-created'):
?>
A new event has been created, located here: <a href="<?php echo $pbjEventLink;?>"><?php echo $event->getTitle();?></a>. 
The event page is the source of information about the event, and other recipients on this email thread can indicate whether or not they'll be attending. So easy!
<?php
break;
case('replyall-post'):
if ($leftOffGuests != null && count($leftOffGuests) > 0) {
  echo "Adding other guests to the email thread: <br/>";
  foreach($leftOffGuests as $g) {
    echo $g->recipient . "<br/>";
  }
  echo "<br/>";
}
if ($outGuests != null && count($outGuests) > 0) {
  echo "Removing guests from the email thread who are not participating in the event: <br/>";
  foreach($outGuests as $g) {
    echo $g->recipient . "<br/>";
  }
  echo "<br/>";
}
?>
<?php
break;
default:
?>
<?php
break;
}
?>