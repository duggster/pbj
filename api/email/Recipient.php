<?php
namespace email;

class Recipient {
  //Guest Name
  public $name;
  
  //Guest Email Address
  public $email;
  
  //Guest Name and Email in format: $name <$email>
  public $recipient;
  
  //Guest entity
  public $guest;
  
  public function __construct($name = "", $email = "", $recipient = "", $guest = null) {
    $this->name = $name;
    $this->email = $email;
    $this->recipient = $recipient;
    $this->guest = $guest;
  }
}
?>