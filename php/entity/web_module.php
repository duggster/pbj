<?php
namespace entity;

/**
 * @Entity
 * @Table(name="web_module")
 **/
class WebModule {

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
   * @OneToMany(targetEntity="entity\WebModuleRole", mappedBy="webModule", orphanRemoval=true)
   **/
  private $webModuleRoles;
  public function getWebModuleRoles() { return $this->webModuleRoles; }
  public function setWebModuleRoles($webModuleRoles) { $this->webModuleRoles = $webModuleRoles; }
  
  /**
   * @OneToMany(targetEntity="entity\WebModuleProp", mappedBy="webModule", orphanRemoval=true)
   **/
  private $webModuleProps;
  public function getWebModuleProps() { return $this->webModuleProps; }
  public function setWebModuleProps($webModuleProps) { $this->webModuleProps = $webModuleProps; }
}

?>