<?php
namespace pbj\model\v1_0;

class ServiceError extends BaseModel {
  public $message;
  public $exMessage;
  public $exCode;
  public $exFile;
  public $exLine;
  public $exTrace;
  public $exPrevious;
  
  public function __toString() {
    $s = $this->message;
    //TODO: add other fields
    return $s;
  }
  
  public static function createFromException($ex) {
    $e = null;
    if ($ex != null) {
      $e = new ServiceError();
      $e->exMessage = $ex->getMessage();
      $e->exCode = $ex->getCode();
      $e->exFile = $ex->getFile();
      $e->exLine = $ex->getLine();
      $e->exTrace = $ex->getTrace();
      $e->exPrevious = self::createFromException($ex->getPrevious());
    }
    return $e;
  }
}

?>