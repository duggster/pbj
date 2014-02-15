<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'GoogleLogin.php';

$login = new GoogleLogin();
$authUrl = $login->getAuthUrl('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

$googleId = (isset($_COOKIE["googleId"]))? $_COOKIE["googleId"] : NULL;
$phpsessid = (isset($_COOKIE[session_name()]))? $_COOKIE[session_name()] : NULL;

?>
<html>
<head>
<title>Generic Login Page</title>
<link rel="stylesheet" href="css/pbj.css">
</head>

<body>
  
  
  Google ID: <?php echo $googleId ?><br/>
  PHP Session: <?php echo session_name() . ": " . $phpsessid ?>
  <br/>
  <br/>
  <a href="<?php echo $authUrl ?>">Login</a> 
  <a href="logout.php">Logout</a> 
  
  
</body>
</html>
