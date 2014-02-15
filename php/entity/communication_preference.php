<?php
namespace entity;

/**
 * @Entity
 * @Table(name="communication_preference")
 **/
class CommunicationPreference {

  /** @Id @Column(name="communication_preference_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }
  
  /** @ManyToOne(targetEntity="entity\User") 
      @JoinColumn(name="user_id", referencedColumnName="user_id") */
  private $user;
  public function getUser() { return $this->user; }
  public function setUser($u) { $this->user = $u; }
  
  /** @Column */
  private $preferenceType;
  public function getPreferenceType() { return $this->preferenceType; }
  public function setPreferenceType($preferenceType) { $this->preferenceType = $preferenceType; }
  
  /** @Column */
  private $handle;
  public function getHandle() { return $this->handle; }
  public function setHandle($handle) { $this->handle = $handle; }
  
  /** @Column */
  private $isActive;
  public function getIsActive() { return $this->isActive; }
  public function setIsActive($isActive) { $this->isActive = $isActive; }
  
  /** @Column */
  private $isPrimary;
  public function getIsPrimary() { return $this->isPrimary; }
  public function setIsPrimary($isPrimary) { $this->isPrimary = $isPrimary; }

}

?>