<?php
namespace entity;

/**
 * @Entity
 * @Table(name="event_web_module_role")
 **/
class EventWebModuleRole {

  /** @Id @Column(name="event_web_module_role_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }
  
  /** @ManyToOne(targetEntity="entity\WebModule", cascade={"remove"}) 
      @JoinColumn(name="web_module_id", referencedColumnName="web_module_id") */
  private $webModule;
  public function getWebModule() { return $this->webModule; }
  public function setWebModule($webModule) { $this->webModule = $webModule; }
  
  /** @ManyToOne(targetEntity="entity\Event")
      @JoinColumn(name="event_id", referencedColumnName="event_id") */
  public $event;
  public function getEvent() { return $this->event; }
  public function setEvent($event) { $this->event = $event; }
  
  /** @Column */
  private $role;
  public function getRole() { return $this->role; }
  public function setRole($role) { $this->role = $role; }
  
  /** @Column */
  private $action;
  public function getAction() { return $this->action; }
  public function setAction($action) { $this->action = $action; }  
}

?>