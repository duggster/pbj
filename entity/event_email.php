<?php
namespace entity;

/**
 * @Entity
 * @Table(name="event_email")
 */
class EventEmail {

  /** @Id @Column(name="event_email_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }

  /** @ManyToOne(targetEntity="entity\Event")
      @JoinColumn(name="event_id", referencedColumnName="event_id") */
  private $event;
  public function getEvent() { return $this->event; }
  public function setEvent($event) { $this->event = $event; }

  /** @Column */
  private $subject;
  public function getSubject() { return $this->subject; }
  public function setSubject($subject) { $this->subject = $subject; }
  
  /** @Column */
  private $fromAddress;
  public function getFromAddress() { return $this->fromAddress; }
  public function setFromAddress($fromAddress) { $this->fromAddress = $fromAddress; }
  
  /** @Column */
  private $toAddresses;
  public function getToAddresses() { return $this->toAddresses; }
  public function setToAddresses($toAddresses) { $this->toAddresses = $toAddresses; }
  
  /** @Column */
  private $ccAddresses;
  public function getCcAddresses() { return $this->ccAddresses; }
  public function setCcAddresses($ccAddresses) { $this->ccAddresses = $ccAddresses; }
  
  /** @Column */
  private $body;
  public function getBody() { return $this->body; }
  public function setBody($body) { $this->body = $body; }
  
  /** @Column */
  private $strippedBody;
  public function getStrippedBody() { return $this->strippedBody; }
  public function setStrippedBody($strippedBody) { $this->strippedBody = $strippedBody; }

  /** @Column(type="datetime") */
  private $timestamp;
  public function getTimestamp() { return $this->timestamp; }
  public function setTimestamp($timestamp) { $this->timestamp = $timestamp; }
}


?>