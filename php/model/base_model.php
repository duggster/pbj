<?php
namespace pbj\model\v1_0;

class BaseModel {
  
  public static function createFromJSON($json) {
    $incoming = json_decode($json);
    return self::createFromAnonObject($incoming);
  }
  
  public static function createFromAnonObject($obj) {
    $inst = new static();
    $vars = get_object_vars($inst);
    foreach($vars as $var=>$val) {
      if (array_key_exists($var, $obj)) {
        $inst->$var = $obj->$var;
      }
    }
    return $inst;
  }
}


?>