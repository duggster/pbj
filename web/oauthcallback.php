<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'GoogleLogin.php';

$login = new GoogleLogin();
$login->authenticateWithCode();
$googleId = $login->getGoogleId();

setcookie("googleId", $googleId);

$login->redirectToApp();


?>