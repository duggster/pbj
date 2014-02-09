<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
date_default_timezone_set("America/New_York");

require_once 'api/RestRequest.php';

function sendEmails($subject, $message) {
  $request = new RestRequest('https://api.mailgun.net/v2/pbj.mailgun.org/messages', 'POST', 
    array(
      'Content-Type' => 'application/x-www-form-urlencoded'
    ),
    array(
      'from' => 'PBJ <pbj@pbj.mailgun.org>',
      'to' => 'duggster@gmail.com, doug.greene@gmail.com',
      'subject' => $subject,
      'text' => $message
    )
  );
  $request->setUsername('api');
  $request->setPassword('key-7-561rktktrrntdrk7gzk675rvb4tlx7');
  $request->execute();
  $responseInfo = $request->getResponseInfo();
  $responseCode = $responseInfo["http_code"];
  var_dump($request);
  return $responseCode;  
}

//$message = 'from=pbj%40pbj.mailgun.org&to=duggster%40gmail.com&subject=Hello&text=Testing%20some%20Mailgun%20awesomness!';
$message = "Testing some Mailgun awesomness!";
sendEmails("emailtest", $message);

?>