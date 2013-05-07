<?php

session_start();
$_SESSION['userid'] = 1;

?>
<html>
<head>
</head>

<body>

  <header>
    <hgroup>
    <h1>PB & J!</h1>
    <h2 id="subtitle"></h2>
    </hgroup>
  </header>
  
  <section id="pageContent">
  </section>
  
  <script type="text/javascript" src="js/jquery-1.8.2.js"></script>
	<script type="text/javascript" src="js/underscore.js"></script>
	<script type="text/javascript" src="js/backbone.js"></script>
  <script type="text/javascript" src="js/pbj.js"></script>

</body>
</html>