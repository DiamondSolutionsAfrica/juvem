<?php

namespace AppBundle\Form;

use AppBundle\Entity\AcquisitionAttribute;
use AppBundle\Entity\Participation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationBaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTypeOptions = array(
            'years'  => range(Date('Y') - 1, Date('Y') + 1),
            'format' => 'dd.M.yyyy'
        );
        $hasDateCheckbox = array(
            'required'   => false,
            'attr'       => array('class' => 'checkbox-smart'),
            'label_attr' => array('class' => 'control-label')
        );
        /** @var Participation $participation */
        $participation = $options['data'];

        $builder
            ->add(
                'salution', ChoiceType::class, array(
                              'label'    => 'Anrede',
                              'choices'  => array('Frau' => 'Frau', 'Herr' => 'Herr'),
                              'expanded' => false
                          )
            )
            ->add('nameFirst', TextType::class, array('label' => 'Vorname'))
            ->add('nameLast', TextType::class, array('label' => 'Nachname'))
            ->add('addressStreet', TextType::class, array('label' => 'Straße u. Hausnummer'))
            ->add('addressZip', TextType::class, array('label' => 'Postleitzahl'))
            ->add('addressCity', TextType::class, array('label' => 'Stadt'))
            ->add('email', TextType::class, array('label' => 'E-Mail'))
            ->add(
                'phoneNumbers', CollectionType::class, array(
                                  'label'        => 'Telefonnummern',
                                  'entry_type'   => PhoneNumberType::class,
                                  'allow_add'    => true,
                                  'allow_delete' => true,
                                  'attr'         => array('aria-describedby' => 'help-info-phone-numbers')
                              )
            )
            ->add(
                'participants', CollectionType::class, array(
                                  'label'         => 'Teilnehmer',
                                  'entry_type'    => ParticipantType::class,
                                  'allow_add'     => true,
                                  'allow_delete'  => true,
                                  'entry_options' => array(
                                      'participation' => $participation
                                  )
                              )
            );

        $event      = $participation->getEvent();
        $attributes = $event->getAcquisitionAttributes(true, false);

        /** @var AcquisitionAttribute $attribute */
        foreach ($attributes as $attribute) {
            $bid              = $attribute->getBid();
            $attributeOptions = array(
                'label'    => $attribute->getFormTitle(),
                'required' => $attribute->isRequired()
            );
            if (ChoiceType::class == $attribute->getFieldType()) {
                $attributeOptions['placeholder'] = 'keine Option gewählt';
            }

            try {
                $fillout                  = $participation->getAcquisitionAttributeFillout($bid);
                $attributeOptions['data'] = $fillout->getValue();
            } catch (\OutOfBoundsException $e) {
                //intentionally left empty
            }
            $builder->add(
                $attribute->getName(),
                $attribute->getFieldType(),
                array_merge($attributeOptions, $attribute->getFieldOptions())
            );
        }

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\Participation',
            )
        );
    }
}
