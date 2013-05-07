<?php
namespace mapping;

require_once '../model/models.php';
require_once '../api/slim.php';

class BaseMapping {

  protected static $routeName = "";
  
  public static function getRef($params) {
    return self::getRefByRoute(static::$routeName, $params);
  }
  
  public static function getRefByRoute($routeName, $params) {
    $slim = \Slim\Slim::getInstance();
    $ref = new Ref();
    $ref->ref = $slim->urlFor($routeName, $params);
    return $ref;
  }
}

?>