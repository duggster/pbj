<?php
namespace pbj\model\v1_0;

class BaseModel {
  
  public static function createFromJSON($json, $convertHyphens = false) {
    $incoming = json_decode($json);
    if ($convertHyphens) {
      //Convert variable names with hyphens to mixedCase instead
      $vars = get_object_vars($incoming);
      foreach($vars as $var=>$val) {
        if (strpos($var, '-') !== false) {
          $firstChar = $var[0]; //we don't want to change the case of the first letter, so keep it for later
          $var = str_replace('-', ' ', $var);
          $var = ucwords($var); //replace the first letter of each word with upper case
          $var = str_replace(' ', '', $var); //remove the whitespace
          $var[0] = $firstChar; //set the first letter back to original case
          $incoming->{$var} = $val; //weird syntax for adding a property to an object dynamically
        }
      }
    }
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