<?php
namespace AppBundle\Entity;

use AppBundle\BitMask\ParticipantFood;
use AppBundle\BitMask\ParticipantStatus;
use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="participant")
 * @ORM\HasLifecycleCallbacks()
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 */
class Participant
{
    use HumanTrait, AcquisitionAttributeFilloutTrait, CreatedModifiedTrait, SoftDeleteTrait;

    const TYPE_GENDER_FEMALE = 1;
    const TYPE_GENDER_MALE   = 2;

    const LABEL_GENDER_FEMALE = 'männlich';
    const LABEL_GENDER_MALE   = 'weiblich';

    /**
     * @ORM\Column(type="integer", name="aid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $aid;

    /**
     * @ORM\ManyToOne(targetEntity="Participation", inversedBy="participants")
     * @ORM\JoinColumn(name="pid", referencedColumnName="pid", onDelete="cascade")
     */
    protected $participation;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     * @Assert\NotBlank()
     */
    protected $gender;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    protected $food = 0;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     */
    protected $birthday;

    /**
     * @ORM\Column(type="text", name="info_medical")
     */
    protected $infoMedical = '';

    /**
     * @ORM\Column(type="text", name="info_general")
     */
    protected $infoGeneral = '';

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    protected $status = 0;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AcquisitionAttributeFillout", cascade={"all"}, mappedBy="participant")
     */
    protected $acquisitionAttributeFillouts;

    /**
     * Contains the list of attendance lists fillouts of this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AttendanceListFillout", mappedBy="participant", cascade={"all"})
     */
    protected $attendanceListsFillouts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->modifiedAt = new \DateTime();
        $this->createdAt  = new \DateTime();

        $this->acquisitionAttributeFillouts = new ArrayCollection();
    }

    /**
     * Get aid
     *
     * @return integer
     */
    public function getAid()
    {
        return $this->aid;
    }

    /**
     * Set gender
     *
     * @param integer $gender
     *
     * @return self
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @param bool $formatted Set to true to get gender label
     * @return int
     */
    public function getGender($formatted = false)
    {
        if ($formatted) {
            switch ($this->gender) {
                case self::TYPE_GENDER_MALE:
                    return self::LABEL_GENDER_MALE;
                case self::TYPE_GENDER_FEMALE:
                    return self::LABEL_GENDER_FEMALE;
            }
        }

        return $this->gender;
    }

    /**
     * Set food
     *
     * @param integer|ParticipantFood $food
     *
     * @return Participant
     */
    public function setFood($food)
    {
        if ($food instanceof ParticipantFood) {
            $food = $food->getValue();
        }

        $this->food = $food;

        return $this;
    }

    /**
     * Get food
     *
     * @param bool $asMask Set to true to get value as mask
     * @return integer|ParticipantFood
     */
    public function getFood($asMask = false)
    {
        if ($asMask) {
            return new ParticipantFood($this->food);
        }

        return $this->food;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     *
     * @return Participant
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Get age of participant at the related event
     *
     * @param int|null $precision If you want the result to be rounded with round(), specify precision here
     * @return float              Age in years
     */
    public function getAgeAtEvent($precision = null)
    {
        $event = $this->getEvent();
        return EventRepository::age($this->getBirthday(), $event->getStartDate(), $precision);
    }

    /**
     * Check if participant has birthday at related event
     *
     * @return bool True if so
     */
    public function hasBirthdayAtEvent()
    {
        $event = $this->getEvent();
        return EventRepository::hasBirthdayInTimespan(
            $this->getBirthday(), $event->getStartDate(), $event->getEndDate()
        );
    }

    /**
     * Set infoMedical
     *
     * @param string $infoMedical
     *
     * @return Participant
     */
    public function setInfoMedical($infoMedical)
    {
        if ($infoMedical === null) {
            //due to issue https://github.com/symfony/symfony/issues/5906
            $infoMedical = '';
        }

        $this->infoMedical = $infoMedical;

        return $this;
    }

    /**
     * Get infoMedical
     *
     * @return string
     */
    public function getInfoMedical()
    {
        return $this->infoMedical;
    }

    /**
     * Set infoGeneral
     *
     * @param string $infoGeneral
     *
     * @return Participant
     */
    public function setInfoGeneral($infoGeneral)
    {
        if ($infoGeneral === null) {
            //due to issue https://github.com/symfony/symfony/issues/5906
            $infoGeneral = '';
        }

        $this->infoGeneral = $infoGeneral;

        return $this;
    }

    /**
     * Get infoGeneral
     *
     * @return string
     */
    public function getInfoGeneral()
    {
        return $this->infoGeneral;
    }

    /**
     * Set participation
     *
     * @param \AppBundle\Entity\Participation $participation
     *
     * @return Participant
     */
    public function setParticipation(Participation $participation = null)
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Get participation
     *
     * @return \AppBundle\Entity\Participation
     */
    public function getParticipation()
    {
        return $this->participation;
    }


    /**
     * Get event from participation
     *
     * @return \AppBundle\Entity\Event|null
     */
    public function getEvent()
    {
        $participation = $this->getParticipation();
        if (!$participation) {
            return null;
        }

        return $participation->getEvent();
    }

    /**
     * Set status
     *
     * @param integer|ParticipantStatus $status
     *
     * @return Participant
     */
    public function setStatus($status)
    {
        if ($status instanceof ParticipantStatus) {
            $status = $status->getValue();
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @param bool $asMask Set to true to get value as mask
     * @return integer|ParticipantStatus
     */
    public function getStatus($asMask = false)
    {
        if ($asMask) {
            return new ParticipantStatus($this->status);
        }

        return $this->status;
    }

    /**
     * Check if this participant is confirmed
     *
     * @return bool
     */
    public function isConfirmed()
    {
        $status = $this->getStatus(true);
        return $status->has(ParticipantStatus::TYPE_STATUS_CONFIRMED);
    }

    /**
     * Check if this participant is withdrawn
     *
     * @return bool
     */
    public function isWithdrawn()
    {
        $status = $this->getStatus(true);
        return $status->has(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
    }

    /**
     * Check if there is withdraw requested for this participant
     *
     * @return bool
     */
    public function isWithdrawRequested()
    {
        $status = $this->getStatus(true);
        return $status->has(ParticipantStatus::TYPE_STATUS_WITHDRAW_REQUESTED);
    }

    /**
     * Set this participant as withdrawn
     *
     * @param   bool $withdrawn New value
     * @return self
     */
    public function setIsWithdrawn($withdrawn = true)
    {
        $status = $this->getStatus(true);
        if ($withdrawn) {
            $status->enable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
        } else {
            $status->disable(ParticipantStatus::TYPE_STATUS_WITHDRAWN);
        }
        return $this->setStatus($status);
    }

    /**
     * Mark withdraw requested for this participant
     *
     * @param   bool $withdrawn New value
     * @return self
     */
    public function setIsWithdrawRequested($withdrawn = true)
    {
        $status = $this->getStatus(true);
        if ($withdrawn) {
            $status->enable(ParticipantStatus::TYPE_STATUS_WITHDRAW_REQUESTED);
        } else {
            $status->disable(ParticipantStatus::TYPE_STATUS_WITHDRAW_REQUESTED);
        }
        return $this->setStatus($status);
    }


    /**
     * Add attendanceListsFillout
     *
     * @param AttendanceListFillout $attendanceListsFillout
     *
     * @return Participant
     */
    public function addAttendanceListsFillout(AttendanceListFillout $attendanceListsFillout)
    {
        $this->attendanceListsFillouts[] = $attendanceListsFillout;

        return $this;
    }

    /**
     * Remove attendanceListsFillout
     *
     * @param AttendanceListFillout $attendanceListsFillout
     */
    public function removeAttendanceListsFillout(AttendanceListFillout $attendanceListsFillout)
    {
        $this->attendanceListsFillouts->removeElement($attendanceListsFillout);
    }

    /**
     * Get attendanceListsFillouts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttendanceListsFillouts()
    {
        return $this->attendanceListsFillouts;
    }
}
