<?php
namespace entity;

/**
 * @Entity
 * @Table(name="user")
 **/
class User {
  
  /** @Id @Column(name="user_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }
  
  /** @Column */
  private $name;
  public function getName() { return $this->name; }
  public function setName($name) { $this->name = $name; }
  
  
  /** @Column */
  private $isActive;
  public function getIsActive() { return $this->isActive; }
  public function setIsActive($isActive) { $this->isActive = $isActive; }
  
  /** @Column */
  private $googleId;
  public function getGoogleId() { return $this->googleId; }
  public function setGoogleId($googleId) { $this->googleId = $googleId; }
  /**
   * @OneToMany(targetEntity="entity\CommunicationPreference", mappedBy="user", cascade={"persist", "remove"})
   **/
  private $communicationPreferences;
  public function getCommunicationPreferences() { return $this->communicationPreferences; }
  public function setCommunicationPreferences($communicationPreferences) { $this->communicationPreferences = $communicationPreferences; }
  
  private $userFamily;
  
}


?>