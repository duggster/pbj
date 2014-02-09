<?php
namespace entity;

/**
 * @Entity
 * @Table(name="event_web_module")
 **/
class EventWebModule {

  /** @Id @Column(name="web_module_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }
  
  /** @Column */
  private $title;
  public function getTitle() { return $this->title; }
  public function setTitle($title) { $this->title = $title; }
  
  /** @Column */
  private $controllerName;
  public function getControllerName() { return $this->controllerName; }
  public function setControllerName($controllerName) { $this->controllerName = $controllerName; }
  
  /** @Column */
  private $isEventDefault;
  public function getIsEventDefault() { return $this->isEventDefault; }
  public function setIsEventDefault($isEventDefault) { $this->isEventDefault = $isEventDefault; }
  
  /**
   * @OneToMany(targetEntity="entity\WebModuleRole", mappedBy="event", orphanRemoval=true)
   **/
  private $webModuleRoles;
  public function getWebModuleRoles() { return $this->webModuleRoles; }
  public function setWebModuleRoles($webModuleRoles) { $this->webModuleRoles = $webModuleRoles; }
  
}

?>