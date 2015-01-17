<?php
$ISDEBUG = true;
$ISDEBUGOFFLINE = true;

ini_set('display_errors', (($ISDEBUG)?'on':'off'));
error_reporting(E_ALL);
date_default_timezone_set("America/New_York");

global $env;
$LOG_CONFIG = "$env/log4php.xml";

$PBJ_PROTOCOL = 'http';
$PBJ_URL = "$PBJ_PROTOCOL://pbj-local";

//Google OAuth Parameters
$GOOGLE_OFFLINE = $ISDEBUGOFFLINE;
$GOOGLE_APP_NAME = 'PB&J';
$GOOGLE_CLIENT_ID = '232676017603.apps.googleusercontent.com';
$GOOGLE_CLIENT_SECRET = '28eHEt5_gcY31qVAeQoCTRA_';
$GOOGLE_REDIRECT_URI = $PBJ_URL . '/web/oauthcallback.php';

//Doctrine ORM Parameters
$DOCTRINE_DEVMODE = $ISDEBUG;
$DOCTRINE_DBPARAMS = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'pbjadmin',
    'password' => 'pbjadmin',
    'dbname'   => 'pbj'
);

//Mailgun Parameters
$MAILGUN_OFFLINE = $ISDEBUGOFFLINE;
$MAILGUN_OFFLINE_DIR = __DIR__.'/../../api/email/emails';
$MAILGUN_OFFLINE_URL = "$PBJ_URL/api/email/emails";
$MAILGUN_TEST = true; //true means don't send emails to recipients
$MAILGUN_TEST_TO = 'duggster@gmail.com';
$MAILGUN_SUBDOMAIN = 'pbj';
$MAILGUN_URL = "https://api.mailgun.net/v2/$MAILGUN_SUBDOMAIN.mailgun.org/messages";
$MAILGUN_ROUTES_URL = 'https://api.mailgun.net/v2/routes';
$MAILGUN_USERNAME = 'api';
$MAILGUN_PASSWORD = 'key-7-561rktktrrntdrk7gzk675rvb4tlx7';

?>