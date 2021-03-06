<?php
namespace entity;

/**
 * @Entity
 * @Table(name="event_message")
 * @HasLifecycleCallbacks
 */
class EventMessage {

  /** @Id @Column(name="event_message_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }

  /** @ManyToOne(targetEntity="entity\Event")
      @JoinColumn(name="event_id", referencedColumnName="event_id") */
  private $event;
  public function getEvent() { return $this->event; }
  public function setEvent($event) { $this->event = $event; }

  /** @ManyToOne(targetEntity="entity\User")
      @JoinColumn(name="user_id", referencedColumnName="user_id") */
  private $user;
  public function getUser() { return $this->user; }
  public function setUser($user) { $this->user = $user; }

  /** @OneToOne(targetEntity="entity\EventMessage") 
      @JoinColumn(name="parent_message_id", referencedColumnName="event_message_id") */
  private $parentMessage;
  public function getParentMessage() { return $this->parentMessage; }
  public function setParentMessage($parentMessage) { $this->parentMessage = $parentMessage; }

  /** @Column(type="datetime") */
  private $messageTimestamp;
  public function getMessageTimestamp() { return $this->messageTimestamp; }
  public function setMessageTimestamp($messageTimestamp) { $this->messageTimestamp = $messageTimestamp; }
  
  /**
   * @PrePersist
   * This solution from http://stackoverflow.com/questions/7698625/doctrine-2-1-datetime-column-default-value?rq=1
   */
  public function onPrePersistSetTimestamp() {
    $this->messageTimestamp = new \DateTime();
  }

  /** @Column */
  private $message;
  public function getMessage() { return $this->message; }
  public function setMessage($message) { $this->message = $message; }
 

}


?>