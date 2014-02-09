<?php
namespace entity;

/**
 * @Entity
 * @Table(name="guest_link")
 **/
class GuestLink {

  /** @Id @Column(name="guest_link_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }
  
  /**
   * @OneToMany(targetEntity="entity\Guest", mappedBy="guestLink", orphanRemoval=false)
   **/
  private $guests;
  public function getGuests() { return $this->guests; }
  public function setGuests($guests) { $this->guests = $guests; }
}

?>