<?php
namespace entity;

/**
 * @Entity
 * @Table(name="web_module_role")
 **/
class WebModuleRole {

  /** @Id @Column(name="web_module_role_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }
  
  /** @ManyToOne(targetEntity="entity\WebModule", cascade={"remove"}) 
      @JoinColumn(name="web_module_id", referencedColumnName="web_module_id") */
  private $webModule;
  public function getWebModule() { return $this->webModule; }
  public function setWebModule($webModule) { $this->webModule = $webModule; }
  
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