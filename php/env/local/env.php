<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
date_default_timezone_set("America/New_York");

$ISDEBUG = true;

//Google OAuth Parameters
$GOOGLE_OFFLINE = true;
$GOOGLE_APP_NAME = 'PB&J';
$GOOGLE_CLIENT_ID = '232676017603.apps.googleusercontent.com';
$GOOGLE_CLIENT_SECRET = '28eHEt5_gcY31qVAeQoCTRA_';
$GOOGLE_REDIRECT_URI = 'http://localhost/pbj/web/oauthcallback.php';

//Doctrine ORM Parameters
$DOCTRINE_DEVMODE = $ISDEBUG;
$DOCTRINE_DBPARAMS = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'pbjadmin',
    'password' => 'pbjadmin',
    'dbname'   => 'pbj'
);

?>