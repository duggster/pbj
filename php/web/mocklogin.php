<?php
$message = "";
$messageColor = "#F77";
if (isset($_GET["email"])) {
  $email = $_GET["email"];
  $pdo = new PDO("mysql:host=localhost;dbname=pbj", "pbjadmin", "pbjadmin");
  $row = $pdo->query("SELECT u.google_id, u.user_id, u.name FROM user u, communication_preference cp WHERE cp.user_id = u.user_id AND cp.handle = '$email'")->fetch(PDO::FETCH_ASSOC);
  $googleId = $row["google_id"];
  $userId = $row["user_id"];
  $name = $row["name"];
  if ($userId != NULL) {
    if ($googleId == NULL || $googleId == "") {
      $max = $pdo->query("SELECT MAX(u.google_id) FROM user u")->fetchColumn();
      $googleId = $max+1;
      try { 
        $pdo->beginTransaction();
        $s = $pdo->prepare("UPDATE user SET google_id=:googleid WHERE user_id=:userid");
        $s->bindParam(":googleid", $googleId);
        $s->bindParam(":userid", $userId);
        $s->execute();
        $pdo->commit();
      } catch(PDOExecption $e) { 
        $pdo->rollback(); 
        print "Error!: " . $e->getMessage() . "</br>";
      }
    }
    setcookie("googleId", $googleId);
    $message = "LOGIN as $name";
    $messageColor = "#7F7";
    header('Location: pbj.php');
  }
  else {
    $message = "No user found.";
  }
}

?>

<html>
<head>
<title>Mock Login Page</title>
<style>
body {
  font-family: Trebuchet, sans-serif;
  font-size: .8em;
}
</style>
</head>

<body>
<h3>Temp Login</h3>
<form method="GET" action="mocklogin.php">
  <span style="background-color: <?php echo $messageColor?>; color: #FFF; font-weight: bold; padding: 5px;"><?php echo $message; ?></span><br/><br/>
  Enter email: <input type="text" name="email" autofocus="true" value="<?php echo $email; ?>"/><br/>
  <input type="submit" value="Login"/>
</form>
</body>
</html>