<?php
namespace entity;

/**
 * @Entity
 * @Table(name="guest")
 **/
class Guest {

  /** @Id @Column(name="guest_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }
  
  /** @ManyToOne(targetEntity="entity\User") 
      @JoinColumn(name="user_id", referencedColumnName="user_id") */
  private $user;
  public function getUser() { return $this->user; }
  public function setUser($u) { $this->user = $u; }
  
  /** @ManyToOne(targetEntity="entity\Event")
      @JoinColumn(name="event_id", referencedColumnName="event_id") */
  public $event;
  public function getEvent() { return $this->event; }
  public function setEvent($event) { $this->event = $event; }
  
  /** @Column */
  private $status;
  public function getStatus() { return $this->status; }
  public function setStatus($status) { $this->status = $status; }
  
  /** @Column */
  private $isOrganizer;
  public function getIsOrganizer() { return $this->isOrganizer; }
  public function setIsOrganizer($isOrganizer) { $this->isOrganizer = $isOrganizer; }
  
  /** @Column */
  private $notifyPref;
  public function getNotifyPref() { return $this->notifyPref; }
  public function setNotifyPref($notifyPref) { $this->notifyPref = $notifyPref; }
  
  /** @Column */
  private $comments;
  public function getComments() { return $this->comments; }
  public function setComments($comments) { $this->comments = $comments; }
  
  /** @ManyToOne(targetEntity="entity\GuestLink") 
      @JoinColumn(name="guest_link_id", referencedColumnName="guest_link_id") */
  private $guestLink;
  public function getGuestLink() { return $this->guestLink; }
  public function setGuestLink($guestLink) { $this->guestLink = $guestLink; }

}

?>