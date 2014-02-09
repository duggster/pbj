<?php
namespace mapping;
use \pbj\model\v1_0 as model;

class WebModule extends BaseMapping {
  
  protected static $routeName = "GET-WebModule";
  
  public static function fromEntity($e) {
    $m = new WebModule();
    
    $m->id = $e->getId();
    $m->title = $e->getTitle();
    $m->controllerName = $e->getControllerName();
    $m->isEventDefault = $e->getIsEventDefault() == 1;
    
    $m->props = array();
    $entityprops = $e->getWebModuleProps();
    foreach($entityprops as $entityprop) {
      $prop = new model\WebModuleProp();
      $prop->propName = $entityprop->getPropName();
      $prop->propValue = $entityprop->getPropValue();
      $prop->isReadonly = $entityprop->getReadonly() == 1;
      $m->props[] = $prop;
    }
    

    return $m;
  }
  
  public static function toEntity($m) {
    $e = new \entity\WebModule();
    if (isset($m->id)) {
      $e->setId($m->id);
    }
        
    $e->setTitle($m->title);
    $e->setControllerName($m->controllerName);
    $e->setIsEventDefault($m->isEventDefault);
    
    return $e;
  }
  
}

?>