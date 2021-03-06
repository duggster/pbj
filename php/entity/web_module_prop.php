<?php
namespace entity;

/**
 * @Entity
 * @Table(name="web_module_prop")
 **/
class WebModuleProp {

  /** @Id @Column(name="web_module_prop_id")
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
  private $propName;
  public function getPropName() { return $this->propName; }
  public function setPropName($propName) { $this->propName = $propName; }
  
  /** @Column */
  private $propValue;
  public function getPropValue() { return $this->propValue; }
  public function setPropValue($propValue) { $this->propValue = $propValue; }
  
  /** @Column */
  private $readonly;
  public function getReadonly() { return $this->readonly; }
  public function setReadonly($readonly) { $this->readonly = $readonly; }
}

?>