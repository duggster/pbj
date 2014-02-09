<?php 
namespace entity;

/**
 * @Entity
 * @Table(name="event")
 */
class Event {

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
  private $eventDate;
  public function getEventDate() { return $this->eventDate; }
  public function setEventDate($eventDate) { $this->eventDate = $eventDate; }
  
  /** @Column */
  private $eventTime;
  public function getEventTime() { return $this->eventTime; }
  public function setEventTime($eventTime) { $this->eventTime = $eventTime; }
  
  /** @Column */
  private $isPublished;
  public function getIsPublished() { return $this->isPublished; }
  public function setIsPublished($isPublished) { $this->isPublished = $isPublished; }
  
  /** @Column */
  private $htmlDescription;
  public function getHtmlDescription() { return $this->htmlDescription; }
  public function setHtmlDescription($htmlDescription) { $this->htmlDescription = $htmlDescription; }
  
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