<?php
require_once __DIR__.'/../../env/env.php';
$defined_vars = get_defined_vars();
$ctx = $defined_vars["inst"];
extract($ctx->vars);
?>

<html>
<body style="font-family:'Trebuchet MS'; font-size:1em;">
<?php 
echo $ctx->content;
?>
<hr/>
<div style="background-color:#AAB; color:#FFF; font-weight:bold; padding:4px;">PBJ</div>
<?php
if (isset($event) && $event != null) {
?>
 <span style="font-weight:normal;">Message from </span><a href="<?php echo $pbjEventLink ?>"><?php echo $event->getTitle() ?></a>
<?php
}
?>
<?php
if (isset($guest) && $guest != null) {
?>
Your current status: <span style="font-weight: bold; font-size: 1.2em;"><?php echo $guest->getStatus(); ?></span><br/><br/>
<span style="line-height:2em;">Change your status to: 
<?php
$changeto = (($guest->getStatus() == "in") ? "out" : "in");
?>
<a href="<?php echo "$pbjGuestStatusLink/$changeto"; ?>" style="padding:10px; border:1px solid #AAAAAA"><?php echo $changeto; ?></a>
<?php
}
?>
<div>Sent from <a href="<?php echo $pbjLink ?>">PBJ</a></div>
</body>
</html>