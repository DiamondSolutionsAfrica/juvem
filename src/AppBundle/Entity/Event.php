<?php
namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable
 * @ORM\Entity
 * @ORM\Table(name="event")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EventRepository")
 */
class Event
{
    const DATE_FORMAT_DATE      = 'd.m.y';
    const DATE_FORMAT_TIME      = 'H:i';
    const DATE_FORMAT_DATE_TIME = 'd.m.y H:i';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $eid;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Column(type="date", name="start_date")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     */
    protected $startDate;

    /**
     * Defines the start time of the event. May be null for so called full day events
     *
     * @ORM\Column(type="time", name="start_time", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $startTime;

    /**
     * @ORM\Column(type="date", name="end_date", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $endDate;

    /**
     * @ORM\Column(type="time", name="end_time", nullable=true)
     * @Assert\Type("\DateTime")
     */
    protected $endTime;

    /**
     * @ORM\Column(type="boolean", name="is_active")
     */
    protected $isActive;

    /**
     * @ORM\Column(type="boolean", name="is_visible")
     */
    protected $isVisible;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", name="modified_at")
     */
    protected $modifiedAt;

    /**
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt = null;

    /**
     * @Vich\UploadableField(mapping="event_image", fileNameProperty="imageFilename")
     *
     * @var File
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, name="image_filename", nullable=true)
     *
     * @var string
     */
    private $imageFilename;

    /**
     * Contains the acquisition attributes assigned to this event
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\AcquisitionAttribute", inversedBy="events")
     * @ORM\JoinTable(name="event_acquisition_attribute",
     *      joinColumns={@ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="bid", referencedColumnName="bid",
     *      onDelete="CASCADE")}
     * )
     */
    protected $acquisitionAttributes;

    /**
     * Contains the participations assigned to this event
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participation", mappedBy="event", cascade={"remove"})
     */
    protected $participations;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="assignedEvents")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL")
     */
    protected $assignedUser;

    /**
     * CONSTRUCTOR
     */
    public function __construct()
    {
        $this->participations        = new ArrayCollection();
        $this->acquisitionAttributes = new ArrayCollection();
    }

    /**
     * Get eid
     *
     * @return integer
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Event
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Event
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return Event
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Returns true if a start time is set
     *
     * @param  boolean $value Unprocessed value
     * @return bool
     */
    public function hasStartTime($value = null)
    {
        return (bool)$this->startTime;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Event
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Returns true if a end date is set
     *
     * @param  boolean $value Unprocessed value
     * @return bool
     */
    public function hasEndDate($value = null)
    {
        return (bool)$this->endDate;
    }


    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return Event
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Returns true if a end time is set
     *
     * @param  boolean $value Unprocessed value
     * @return bool
     */
    public function hasEndTime($value = null)
    {
        return (bool)$this->endTime;
    }


    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Event
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Set isVisible
     *
     * @param boolean $isVisible
     *
     * @return Event
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    /**
     * Get isVisible
     *
     * @return boolean
     */
    public function isVisible()
    {
        return $this->isVisible;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Get isVisible
     *
     * @return boolean
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     */
    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->modifiedAt = new \DateTime('now');
        }
    }

    /**
     * @return File
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @param string $imageFilename
     */
    public function setImageFilename($imageFilename)
    {
        $this->imageFilename = $imageFilename;
    }

    /**
     * @return string
     */
    public function getImageFilename()
    {
        return $this->imageFilename;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtNow()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Event
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setModifiedAtNow()
    {
        $this->modifiedAt = new \DateTime();
    }

    /**
     * Set modifiedAt
     *
     * @param \DateTime $modifiedAt
     *
     * @return Event
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * Get modifiedAt
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     *
     * @return Event
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Add participation
     *
     * @param \AppBundle\Entity\Participation $participation
     *
     * @return Event
     */
    public function addParticipation(\AppBundle\Entity\Participation $participation)
    {
        $this->participations[] = $participation;

        return $this;
    }

    /**
     * Remove participation
     *
     * @param \AppBundle\Entity\Participation $participation
     */
    public function removeParticipation(\AppBundle\Entity\Participation $participation)
    {
        $this->participations->removeElement($participation);
    }

    /**
     * Get participations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipations()
    {
        return $this->participations;
    }

    /**
     * Set assignedUser
     *
     * @param \AppBundle\Entity\User $assignedUser
     *
     * @return Event
     */
    public function setAssignedUser(\AppBundle\Entity\User $assignedUser = null)
    {
        $this->assignedUser = $assignedUser;

        return $this;
    }

    /**
     * Get assignedUser
     *
     * @return \AppBundle\Entity\User
     */
    public function getAssignedUser()
    {
        return $this->assignedUser;
    }

    /**
     * Add an acquisition attribute assignment to this event
     *
     * @param \AppBundle\Entity\AcquisitionAttribute $acquisitionAttribute
     *
     * @return Event
     */
    public function addAcquisitionAttribute(\AppBundle\Entity\AcquisitionAttribute $acquisitionAttribute)
    {
        $this->acquisitionAttributes[] = $acquisitionAttribute;

        return $this;
    }

    /**
     * Remove an acquisition attribute assignment from this event
     *
     * @param \AppBundle\Entity\AcquisitionAttribute $acquisitionAttribute
     */
    public function removeAcquisitionAttribute(\AppBundle\Entity\AcquisitionAttribute $acquisitionAttribute)
    {
        $this->acquisitionAttributes->removeElement($acquisitionAttribute);
    }

    /**
     * Get acquisition attributes assigned to this event
     *
     * @param bool $includeParticipationFields
     * @param bool $includeParticipantFields
     * @return ArrayCollection
     */
    public function getAcquisitionAttributes($includeParticipationFields = true, $includeParticipantFields = true)
    {
        if ($includeParticipationFields && $includeParticipantFields) {
            return $this->acquisitionAttributes;
        }
        $acquisitionAttributes = array();

        /** @var AcquisitionAttribute $acquisitionAttribute */
        foreach ($this->acquisitionAttributes as $acquisitionAttribute) {
            if (($includeParticipationFields && $acquisitionAttribute->getUseAtParticipation()) ||
                ($includeParticipantFields && $acquisitionAttribute->getUseAtParticipant())
            ) {
                $acquisitionAttributes[] = $acquisitionAttribute;
            }
        }

        return $acquisitionAttributes;
    }
}
