<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Entity;

use AppBundle\Entity\Audit\CreatedModifiedTrait;
use AppBundle\Entity\Audit\SoftDeleteTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as FormChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as FormTextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType as FormTextType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="acquisition_attribute")
 * @Gedmo\SoftDeleteable(fieldName="deleted_at", timeAware=false)
 * @ORM\HasLifecycleCallbacks()
 */
class AcquisitionAttribute
{
    use CreatedModifiedTrait, SoftDeleteTrait;

    const LABEL_FIELD_TEXT     = 'Textfeld (Einzeilig)';
    const LABEL_FIELD_TEXTAREA = 'Textfeld (Mehrzeilig)';
    const LABEL_FIELD_CHOICE   = 'Auswahl';

    /**
     * @ORM\Column(type="integer", name="bid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $bid;

    /**
     * @ORM\Column(type="string", length=255, name="management_title")
     * @Assert\NotBlank()
     */
    protected $managementTitle;

    /**
     * @ORM\Column(type="string", length=255, name="management_description")
     * @Assert\NotBlank()
     */
    protected $managementDescription;

    /**
     * @ORM\Column(type="string", length=255, name="form_title")
     * @Assert\NotBlank()
     */
    protected $formTitle;

    /**
     * @ORM\Column(type="text", name="form_description", length=65535)
     * @Assert\NotBlank()
     */
    protected $formDescription;

    /**
     * @ORM\Column(type="string", length=255, name="field_type")
     */
    protected $fieldType = FormTextType::class;

    /**
     * @ORM\Column(type="json_array", length=16777215, name="field_options", nullable=true)
     */
    protected $fieldOptions = array();

    /**
     * @ORM\Column(name="use_at_participation", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $useAtParticipation = false;

    /**
     * @ORM\Column(name="use_at_participant", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $useAtParticipant = false;

    /**
     * @ORM\Column(name="is_required", type="smallint", options={"unsigned":true,"default":0})
     *
     * @var boolean
     */
    protected $isRequired = false;

    /**
     * Contains the events which use this attribute
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Event", mappedBy="acquisitionAttributes")
     */
    protected $events;

    /**
     * Contains the participants assigned to this participation
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AcquisitionAttributeFillout", cascade={"all"},
     *                                                                             mappedBy="attribute")
     */
    protected $fillouts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->events   = new ArrayCollection();
        $this->fillouts = new ArrayCollection();
    }

    /**
     * Get bid
     *
     * @return integer
     */
    public function getBid()
    {
        return $this->bid;
    }

    /**
     * Get the name for the field, used in forms
     *
     * @return string
     */
    public function getName()
    {
        return 'acq_field_' . $this->bid;
    }

    /**
     * Set managementTitle
     *
     * @param string $managementTitle
     *
     * @return AcquisitionAttribute
     */
    public function setManagementTitle($managementTitle)
    {
        $this->managementTitle = $managementTitle;

        return $this;
    }

    /**
     * Get managementTitle
     *
     * @return string
     */
    public function getManagementTitle()
    {
        return $this->managementTitle;
    }

    /**
     * Set managementDescription
     *
     * @param string $managementDescription
     *
     * @return AcquisitionAttribute
     */
    public function setManagementDescription($managementDescription)
    {
        $this->managementDescription = $managementDescription;

        return $this;
    }

    /**
     * Get managementDescription
     *
     * @return string
     */
    public function getManagementDescription()
    {
        return $this->managementDescription;
    }

    /**
     * Set formTitle
     *
     * @param string $formTitle
     *
     * @return AcquisitionAttribute
     */
    public function setFormTitle($formTitle)
    {
        $this->formTitle = $formTitle;

        return $this;
    }

    /**
     * Get formTitle
     *
     * @return string
     */
    public function getFormTitle()
    {
        return $this->formTitle;
    }

    /**
     * Set formDescription
     *
     * @param string $formDescription
     *
     * @return AcquisitionAttribute
     */
    public function setFormDescription($formDescription)
    {
        $this->formDescription = $formDescription;

        return $this;
    }

    /**
     * Get formDescription
     *
     * @return string
     */
    public function getFormDescription()
    {
        return $this->formDescription;
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
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Set fieldType
     *
     * @param string $fieldType
     *
     * @return AcquisitionAttribute
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        if ($fieldType == FormChoiceType::class) {
            $options             = $this->getFieldOptions();
            $options['expanded'] = true;
            $this->setFieldOptions($options);
        } else {
            $this->setFieldOptions(array());
        }

        return $this;
    }

    /**
     * Get fieldType
     *
     * @param bool $asLabel Set to true to return as label
     *
     * @return string
     */
    public function getFieldType($asLabel = false)
    {
        if ($asLabel) {
            switch ($this->fieldType) {
                case FormTextType::class:
                    return self::LABEL_FIELD_TEXT;
                case FormTextareaType::class;
                    return self::LABEL_FIELD_TEXTAREA;
                case FormChoiceType::class;
                    return self::LABEL_FIELD_CHOICE;
            }
        }

        return $this->fieldType;
    }

    /**
     * Set fieldOptions
     *
     * @param array $fieldOptions
     *
     * @return AcquisitionAttribute
     */
    public function setFieldOptions($fieldOptions)
    {
        if ($fieldOptions === null) {
            $fieldOptions = array();
        }
        $this->fieldOptions = $fieldOptions;

        return $this;
    }

    /**
     * Get fieldOptions
     *
     * @return array
     */
    public function getFieldOptions()
    {
        $options = $this->fieldOptions;
        if (!$options) {
            $options = [];
        }
        $options['label']    = $this->getFormTitle();
        $options['required'] = $this->isRequired();
        $options['mapped']   = true;

        if ($this->getFieldType() == FormChoiceType::class) {
            $options['placeholder'] = 'keine Option gewählt';
        }

        return $options;
    }

    /**
     * Set field choices if this is a choice attribute
     *
     * @param boolean $multiple
     *
     * @return AcquisitionAttribute
     */
    public function setFieldTypeChoiceType($multiple)
    {
        if ($this->getFieldType() == FormChoiceType::class) {
            $options             = $this->getFieldOptions();
            $options['multiple'] = (bool)$multiple;
            $this->setFieldOptions($options);
        }

        return $this;
    }

    /**
     * Get field choices if this is a choice attribute
     *
     * @return bool
     */
    public function getFieldTypeChoiceType()
    {
        $options = $this->getFieldOptions();

        if ($this->getFieldType() == FormChoiceType::class && isset($options['multiple'])) {
            return $options['multiple'];
        }

        return false;
    }


    /**
     * Set field choices if this is a choice attribute
     *
     * @param array $choices
     *
     * @return AcquisitionAttribute
     */
    public function setFieldTypeChoiceOptions($choices)
    {
        if ($choices === null) {
            $choices = array();
        } elseif (!is_array($choices)) {
            $choicesString = $choices;
            $choices       = array();

            foreach (explode(';', $choicesString) as $choice) {
                $choices[$choice] = sha1($choice);
            }
        }

        if ($this->getFieldType() == FormChoiceType::class) {
            $options            = $this->getFieldOptions();
            $options['choices'] = $choices;
            $this->setFieldOptions($options);
        }

        return $this;
    }

    /**
     * Get field choices if this is a choice attribute
     *
     * @param bool $asArray Set to true to return as array
     *
     * @return array
     */
    public function getFieldTypeChoiceOptions($asArray = false)
    {
        $options = $this->getFieldOptions();

        if ($this->getFieldType() == FormChoiceType::class && isset($options['choices'])) {
            if ($asArray) {
                return $options['choices'];
            } else {
                return implode(';', array_keys($options['choices']));
            }
        }

        if ($asArray) {
            return array();
        }

        return '';
    }

    /**
     * Set useAtParticipation
     *
     * @param boolean $useAtParticipation
     *
     * @return AcquisitionAttribute
     */
    public function setUseAtParticipation($useAtParticipation = true)
    {
        $this->useAtParticipation = (bool)$useAtParticipation;

        return $this;
    }

    /**
     * Get useAtParticipation
     *
     * @return boolean
     */
    public function getUseAtParticipation()
    {
        return (bool)$this->useAtParticipation;
    }

    /**
     * Set useAtParticipant
     *
     * @param boolean $useAtParticipant
     *
     * @return AcquisitionAttribute
     */
    public function setUseAtParticipant($useAtParticipant = true)
    {
        $this->useAtParticipant = (bool)$useAtParticipant;

        return $this;
    }

    /**
     * Get useAtParticipant
     *
     * @return boolean
     */
    public function getUseAtParticipant()
    {
        return (bool)$this->useAtParticipant;
    }

    /**
     * Set isRequired
     *
     * @param boolean $isRequired
     *
     * @return AcquisitionAttribute
     */
    public function setIsRequired($isRequired = true)
    {
        $this->isRequired = (bool)$isRequired;

        return $this;
    }

    /**
     * Get isRequired
     *
     * @return boolean
     */
    public function isRequired()
    {
        return (bool)$this->isRequired;
    }

    /**
     * Add event
     *
     * @param Event $event
     *
     * @return AcquisitionAttribute
     */
    public function addEvent(Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event
     *
     * @param Event $event
     */
    public function removeEvent(Event $event)
    {
        $this->events->removeElement($event);
    }

    /**
     * Get events
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Add fillout
     *
     * @param \AppBundle\Entity\AcquisitionAttributeFillout $fillout
     *
     * @return AcquisitionAttribute
     */
    public function addFillout(\AppBundle\Entity\AcquisitionAttributeFillout $fillout)
    {
        $this->fillouts[] = $fillout;

        return $this;
    }

    /**
     * Remove fillout
     *
     * @param \AppBundle\Entity\AcquisitionAttributeFillout $fillout
     */
    public function removeFillout(\AppBundle\Entity\AcquisitionAttributeFillout $fillout)
    {
        $this->fillouts->removeElement($fillout);
    }

    /**
     * Get fillouts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFillouts()
    {
        return $this->fillouts;
    }

}
