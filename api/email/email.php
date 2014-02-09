<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once __DIR__.'/../../env/env.php';

$dir = $MAILGUN_OFFLINE_DIR;
$first = "";
$entries = array();
if (is_dir($dir)) {
  $entries = array_diff(scandir($dir, 1), array('..', '.'));
  if (count($entries > 0)) {
    $first = "$MAILGUN_OFFLINE_URL/" . $entries[0];
  }
}

?>
<html>
<head>
<script>
function init() {
  <?php echo "load('$first');"; ?>
}

function load(filename) {
  document.getElementById('f').src = filename;
}
</script>
<style>

a {
  padding: 10px;
  border: 1px solid #FBB;
  border-radius: 3px;
  display: inline-block;
}

a:hover {
  background-color: #FEE;
}

a:active {
  color: #FFF;
}

ul {
  height:300px; 
  border:1px solid #EEE; 
  list-style:none; 
  margin:0; 
  padding:0;
  overflow-y: scroll;
}

</style>
</head>

<body onload="init();">

<div style="float:left; width: 18%;">
<?php
echo "<ul title='$dir'>";
foreach ($entries as $entry) {
  $name = "$MAILGUN_OFFLINE_URL/$entry";
  echo "<li><a href='#' onclick='load(\"$name\"); return false;'>$entry</a></li>\n";
}
?>
</div>
<iframe src="" id="f" style="float:right; width: 80%; height:90%"/>

</body>
</html>