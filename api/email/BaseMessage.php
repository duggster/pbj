<?php 
namespace email;

require_once __DIR__.'/../../env/env.php';

class BaseMessage {
  public $contentName = "";
  public $content = "";
  public $vars = null;
  
  public function __construct() {
    $this->vars = array();
  }
  
  public function prepare() {
    if ($this->vars == null) {
      $this->vars = array();
    }
    global $PBJ_URL;
    $this->vars["pbjLink"] = "$PBJ_URL/web/pbj.php";
    if (isset($this->vars["event"])) {
      $this->vars["pbjEventLink"] = self::getEventPage($this->vars["event"]->getId());
    }
    if (isset($this->vars["guest"])) {
      $this->vars["pbjGuestStatusLink"] = self::getGuestStatusPage($this->vars["guest"]->getId());
    }
  }
  
  public static function getMessageContentByName($contentName, $vars) {
    $inst = new BaseMessage();
    $inst->contentName = $contentName;
    $inst->vars = $vars;
    $inst->prepare();
    return self::getIncludeHtml('EmailContent.php', $inst);
  }
  
  public static function getEmailHtmlByName($contentName, $vars) {
    $content = self::getMessageContentByName($contentName, $vars);
    return self::getEmailHtmlWithContent($content, $vars);
  }
  
  public static function getEmailHtmlWithContent($content, $vars) {
    $inst = new BaseMessage();
    $inst->content = $content;
    $inst->vars = $vars;
    $inst->prepare();
    return self::getIncludeHtml('EmailTemplate.php', $inst);
  }

	public static function getIncludeHtml($filename, $inst) {
    ob_start();
    extract(get_defined_vars());
    include($filename);
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }
  
  public static function getEventPage($eventid) {
    global $PBJ_URL;
    return "$PBJ_URL/web/pbj.php#/event/$eventid";
  }
  
  public static function getGuestStatusPage($guestid) {
    global $PBJ_URL;
    return "$PBJ_URL/web/pbj.php#/guest/$guestid/set-status";
  }
}

?>