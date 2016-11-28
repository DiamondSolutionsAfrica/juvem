<?php
namespace AppBundle\Entity;

use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="participation")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 */
class Participation
{
    use HumanTrait, AcquisitionAttributeFilloutTrait, CreatedModifiedTrait;
    use SoftDeleteTrait {
        setDeletedAt as traitSetDeletedAt;
    }

    /**
     * @ORM\Column(type="integer", name="pid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $pid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event", inversedBy="participations", cascade={"all"})
     * @ORM\JoinColumn(name="eid", referencedColumnName="eid", onDelete="cascade")
     */
    protected $event;

    /**
     * @ORM\Column(type="string", length=64, name="salution")
     */
    protected $salution;

    /**
     * @ORM\Column(type="string", length=128, name="address_street")
     */
    protected $addressStreet;

    /**
     * @ORM\Column(type="string", length=128, name="address_city")
     */
    protected $addressCity;

    /**
     * @ORM\Column(type="string", length=16, name="address_zip")
     */
    protected $addressZip;

    /**
     * @ORM\Column(type="string", length=128, name="email")
     */
    protected $email;

    /**
     * Contains the phone numbers assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\PhoneNumber", cascade={"all"}, mappedBy="participation")
     */
    protected $phoneNumbers;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Participant", cascade={"all"}, mappedBy="participation")
     */
    protected $participants;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AcquisitionAttributeFillout", cascade={"all"}, mappedBy="participation")
     */
    protected $acquisitionAttributeFillouts;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="assignedParticipations")
     * @ORM\JoinColumn(name="uid", referencedColumnName="uid", onDelete="SET NULL")
     */
    protected $assignedUser;

    /**
     * Constructor
     *
     * @param bool $omitPhoneNumberInit If set to false, a single empty phone number is added
     * @param bool $omitParticipantInit If set to false, a single empty participant is added
     */
    public function __construct($omitPhoneNumberInit = false, $omitParticipantInit = false)
    {
        $this->phoneNumbers = new ArrayCollection();
        if (!$omitPhoneNumberInit) {
            $phoneNumber = new PhoneNumber();
            $this->addPhoneNumber($phoneNumber);
        }

        $this->participants = new ArrayCollection();
        if (!$omitParticipantInit) {
            $participant = new Participant();
            $this->addParticipant($participant);
        }

        $this->acquisitionAttributeFillouts = new ArrayCollection();

        $this->modifiedAt = new \DateTime();
        $this->createdAt  = new \DateTime();
    }

    /**
     * Print the name, including first name if available
     *
     * @return string
     */
    public function getName()
    {
        $name = $this->getNameLast();

        if ($this->getNameFirst()) {
            $name .= ', ' . $this->getNameFirst();
        }

        return $name;
    }

    /**
     * Set deletedAt, cascading to participants
     *
     * @param \DateTime $deletedAt
     *
     * @return Event
     */
    public function setDeletedAt($deletedAt)
    {
        $this->traitSetDeletedAt($deletedAt);

        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $participant->setDeletedAt($deletedAt);
        }

        return $this;
    }

    /**
     * Get pid
     *
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set salution
     *
     * @param string $parentSalution Salution
     *
     * @return Participation
     */
    public function setSalution($parentSalution)
    {
        $this->salution = $parentSalution;

        return $this;
    }

    /**
     * Get salution
     *
     * @return string
     */
    public function getSalution()
    {
        return $this->salution;
    }


    /**
     * Set addressStreet
     *
     * @param string $addressStreet
     *
     * @return Participation
     */
    public function setAddressStreet($addressStreet)
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    /**
     * Get addressStreet
     *
     * @return string
     */
    public function getAddressStreet()
    {
        return $this->addressStreet;
    }

    /**
     * Set addressCity
     *
     * @param string $addressCity
     *
     * @return Participation
     */
    public function setAddressCity($addressCity)
    {
        $this->addressCity = $addressCity;

        return $this;
    }

    /**
     * Get addressCity
     *
     * @return string
     */
    public function getAddressCity()
    {
        return $this->addressCity;
    }

    /**
     * Set addressZip
     *
     * @param string $addressZip
     *
     * @return Participation
     */
    public function setAddressZip($addressZip)
    {
        $this->addressZip = $addressZip;

        return $this;
    }

    /**
     * Get addressZip
     *
     * @return string
     */
    public function getAddressZip()
    {
        return $this->addressZip;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Participation
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get phone Numbers
     */
    public function getPhoneNumbers()
    {
        return $this->phoneNumbers;
    }

    /**
     * Add phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     *
     * @return Participation
     */
    public function addPhoneNumber(PhoneNumber $phoneNumber)
    {
        $phoneNumber->setParticipation($this);
        $this->phoneNumbers[] = $phoneNumber;

        return $this;
    }

    /**
     * Remove phoneNumber
     *
     * @param PhoneNumber $phoneNumber
     */
    public function removePhoneNumber(PhoneNumber $phoneNumber)
    {
        $this->phoneNumbers->removeElement($phoneNumber);
    }

    /**
     * Add participant
     *
     * @param \AppBundle\Entity\Participant $participant
     *
     * @return Participation
     */
    public function addParticipant(Participant $participant)
    {
        $participant->setParticipation($this);
        $this->participants[] = $participant;

        return $this;
    }

    /**
     * Remove participant
     *
     * @param \AppBundle\Entity\Participant $participant
     */
    public function removeParticipant(Participant $participant)
    {
        $this->participants->removeElement($participant);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Set event
     *
     * @param Event $event
     *
     * @return Participation
     */
    public function setEvent(Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set assignedUser
     *
     * @param \AppBundle\Entity\User $assignedUser
     *
     * @return Participation
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
     * Check if all related participants are confirmed
     *
     * @return bool
     */
    public function isConfirmed()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_CONFIRMED)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $confirmed New value
     * @return bool
     */
    public function setIsConfirmed($confirmed = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($confirmed) {
                $status->enable(ParticipantStatus::TYPE_STATUS_CONFIRMED);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_CONFIRMED);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Check if all related participants are paid
     *
     * @return bool
     */
    public function isPaid()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_PAID)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $paid New value
     * @return bool
     */
    public function setIsPaid($paid = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($paid) {
                $status->enable(ParticipantStatus::TYPE_STATUS_PAID);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_PAID);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Check if all related participants are withdrawn
     *
     * @return bool
     */
    public function isWithdrawn()
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if (!$status->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set conformation value for all related participants
     *
     * @param   bool $withdrawn New value
     * @return self
     */
    public function setIsWithdrawn($withdrawn = true)
    {
        /** @var Participant $participant */
        foreach ($this->getParticipants() as $participant) {
            $status = $participant->getStatus(true);
            if ($withdrawn) {
                $status->enable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            } else {
                $status->disable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
            }
            $participant->setStatus($status->__toString());
        }
        return $this;
    }

    /**
     * Create a new participation for transmitted event based on data of given participation
     *
     * @param Participation $participationPrevious Data template
     * @param Event         $event                 Event this new participation should belong to
     * @return Participation
     */
    public static function createFromTemplateForEvent(Participation $participationPrevious, Event $event)
    {
        $participation = new self(true, true);
        $participation->setEvent($event);
        $participation->setNameLast($participationPrevious->getNameLast());
        $participation->setNameFirst($participationPrevious->getNameFirst());
        $participation->setAddressCity($participationPrevious->getAddressCity());
        $participation->setAddressStreet($participationPrevious->getAddressStreet());
        $participation->setAddressZip($participationPrevious->getAddressZip());
        $participation->setEmail($participationPrevious->getEmail());
        $participation->setSalution($participationPrevious->getSalution());

        /** @var PhoneNumber $numberPrevious */
        foreach ($participationPrevious->getPhoneNumbers() as $numberPrevious) {
            $number = new PhoneNumber();
            $number->setParticipation($participation);
            $number->setDescription($numberPrevious->getDescription());
            $number->setNumber($numberPrevious->getNumber());
            $participation->addPhoneNumber($number);
        }

        /** @var AcquisitionAttribute $attribute */
        foreach ($event->getAcquisitionAttributes(true, false) as $attribute) {
            try {
                $filloutPrevious = $participationPrevious->getAcquisitionAttributeFillout($attribute->getBid(), false);
            } catch (\OutOfBoundsException $e) {
                continue;
            }
            $fillout = new AcquisitionAttributeFillout();
            $fillout->setParticipation($participation);
            $fillout->setAttribute($attribute);
            $fillout->setValue($filloutPrevious->getValue());
            $participation->addAcquisitionAttributeFillout($fillout);
        }

        /** @var Participant $participantPrevious */
        foreach ($participationPrevious->getParticipants() as $participantPrevious) {
            $participant = new Participant();
            $participant->setParticipation($participation);
            $participant->setNameLast($participantPrevious->getNameLast());
            $participant->setNameFirst($participantPrevious->getNameFirst());
            $participant->setBirthday($participantPrevious->getBirthday());
            $participant->setFood($participantPrevious->getFood());
            $participant->setGender($participantPrevious->getGender());
            $participant->setInfoGeneral($participantPrevious->getInfoGeneral());
            $participant->setInfoMedical($participantPrevious->getInfoMedical());

            /** @var AcquisitionAttribute $attribute */
            foreach ($event->getAcquisitionAttributes(false, true) as $attribute) {
                try {
                    $filloutPrevious = $participantPrevious->getAcquisitionAttributeFillout(
                        $attribute->getBid(), false
                    );
                } catch (\OutOfBoundsException $e) {
                    continue;
                }
                $fillout = new AcquisitionAttributeFillout();
                $fillout->setParticipant($participant);
                $fillout->setAttribute($attribute);
                $fillout->setValue($filloutPrevious->getValue());
                $participant->addAcquisitionAttributeFillout($fillout);
            }
            $participation->addParticipant($participant);
        }

        return $participation;
    }
}
