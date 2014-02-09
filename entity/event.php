<?php 
namespace entity;

/**
 * @Entity
 * @Table(name="event")
 */
class Event {

  public function __construct() {
    $this->guests = new \Doctrine\Common\Collections\ArrayCollection();
  }

  /** @Id @Column(name="event_id")
      @GeneratedValue(strategy="IDENTITY")*/
  private $id;
  public function getId() { return $this->id; }
  public function setId($id) { $this->id = $id; }

  /** @Column(name="title")*/
  private $title;
  public function getTitle() { return $this->title; }
  public function setTitle($title) { $this->title = $title; }
  
  /** @Column */
  private $eventWhen;
  public function getEventWhen() { return $this->eventWhen; }
  public function setEventWhen($eventWhen) { $this->eventWhen = $eventWhen; }
  
  /** @Column */
  private $eventWhere;
  public function getEventWhere() { return $this->eventWhere; }
  public function setEventWhere($eventWhere) { $this->eventWhere = $eventWhere; }
  
  /** @Column */
  private $isShowInfo;
  public function getIsShowInfo() { return $this->isShowInfo; }
  public function setIsShowInfo($isShowInfo) { $this->isShowInfo = $isShowInfo; }
  
  /** @Column */
  private $isPublished;
  public function getIsPublished() { return $this->isPublished; }
  public function setIsPublished($isPublished) { $this->isPublished = $isPublished; }
  
  /** @Column */
  private $htmlDescription;
  public function getHtmlDescription() { return $this->htmlDescription; }
  public function setHtmlDescription($htmlDescription) { $this->htmlDescription = $htmlDescription; }
  
  /** @Column */
  private $emailAddress;
  public function getEmailAddress() { return $this->emailAddress; }
  public function setEmailAddress($emailAddress) { $this->emailAddress = $emailAddress; }
  
  
  /**
   * @OneToMany(targetEntity="entity\Guest", mappedBy="event", orphanRemoval=true)
   **/
  private $guests;
  public function getGuests() { return $this->guests; }
  public function setGuests($guests) { $this->guests = $guests; }
  
  /** 
   * @ManyToMany(targetEntity="entity\WebModule")
   * @JoinTable(name="event_web_module",
   *    joinColumns={@JoinColumn(name="event_id", referencedColumnName="event_id")},
   *    inverseJoinColumns={@JoinColumn(name="web_module_id", referencedColumnName="web_module_id")}
   *    )
  **/
  private $webModules;
  public function getWebModules() { return $this->webModules; }
  public function setWebModules($webModules) { $this->webModules = $webModules; }
}


?>