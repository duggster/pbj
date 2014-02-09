<?php
require_once '../../env/env.php';
require_once '../../entity/doctrine.php';
require_once '../../api/html2text.php';
require_once 'BaseEmail.php';

$em = \getEntityManager();
$e = $em->find('\entity\Event', 127);

$email = new BaseEmail();
$email->event = $e;
$email->contentName = "broadcast-description";
$html = $email->getHtml();
$text = convert_html_to_text($html);

if (isset($_GET["text"])) {
  echo "<pre>$text</pre>";
}
else {
  echo $html;
}


?>