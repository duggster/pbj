<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
date_default_timezone_set("America/New_York");

$ISDEBUG = true;

//Google OAuth Parameters
$GOOGLE_OFFLINE = false;
$GOOGLE_APP_NAME = 'PB&J';
$GOOGLE_CLIENT_ID = '232676017603-e1vfb9ci8qgisqms9c6nda2m2mq842en.apps.googleusercontent.com';
$GOOGLE_CLIENT_SECRET = 'AedJlDsAnTByF0nr5GFaRfjQ';
$GOOGLE_REDIRECT_URI = 'http://pbj-dougandjeanne.rhcloud.com/web/oauthcallback.php';

//Doctrine ORM Parameters
$DOCTRINE_DEVMODE = $ISDEBUG;
$DOCTRINE_DBPARAMS = array(
  'driver'   => 'pdo_mysql',
  'host'     => '127.5.16.1',
  'user'     => 'pbjadmin',
  'password' => 'pbjadmin',
  'dbname'   => 'pbj'
);

?>