<?php

$handles = "Jeff Stricker <jeff@jeffstricker.com>,
 Alex Nguyen <nguyenalext@gmail.com>,
 Jeanne Sun <jeannesun@gmail.com>,
 Joe Mueller <joemueller09@gmail.com>,
 Andrew Scharf <scharf.andrew@gmail.com>,
 Christina Yeh <christina.yeh@gmail.com>,
 John Ho <jyho76@hotmail.com>,
 John Yoo <johnjyoo@gmail.com>,
 Eric Chen <erchen@gmail.com>,
 Kiran Kshatriya <kiran.kshatriya@gmail.com>,
 duggster@gmail.com,
 tofuninja@gmail.com";
 
 $handles = explode(',', $handles);
 if ($handles != NULL && sizeof($handles) > 0) {
  echo "<ul>";
  foreach($handles as $handle) {
    preg_match('/(?P<name>.*)<(?P<email>.*@.*)>/', $handle, $matches);
    $email = $matches['email'];
    if ($email != NULL) {
      $email = str_replace("<", "", $email);
      $email = str_replace(">", "", $email);
      $email = trim($email);
      $name = trim($matches['name']);
    }
    else {
      $email = trim($handle);
      $name = $email;
    }
    
    echo "<li style=''>$name - $email</li>";
  }
  echo "</ul>";
}
echo "done.";


?>

