<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateTypeOptions = array(
            'years'  => range(Date('Y') - 1, Date('Y') + 1),
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
        );
        $smartCheckbox = array(
            'required'   => false,
            'mapped'     => true,
            'attr'       => array('class' => 'checkbox-smart'),
            'label_attr' => array('class' => 'control-label checkbox-smart-label')
        );

        $builder
            ->add('title', TextType::class, array('label' => 'Titel'))
            ->add(
                'description', TextareaType::class,
                array('label' => 'Beschreibung', 'attr' => array('aria-describedby' => 'help-description'))
            )
            ->add(
                'startDate', DateType::class,
                array_merge($dateTypeOptions, array('label' => 'Startdatum'))
            )
            ->add(
                'hasStartTime', CheckboxType::class,
                array_merge($smartCheckbox, array('label' => 'Startzeit'))
            )
            ->add('startTime', TimeType::class, array('label' => 'Startzeit'))
            ->add(
                'hasEndDate', CheckboxType::class,
                array_merge($smartCheckbox, array('label' => 'Enddatum'))
            )
            ->add(
                'endDate', DateType::class,
                array_merge($dateTypeOptions, array('label' => 'Enddatum'))
            )
            ->add(
                'hasEndTime', CheckboxType::class,
                array_merge($smartCheckbox, array('label' => 'Endzeit'))
            )
            ->add('endTime', TimeType::class, array('label' => 'Endzeit'))
            ->add(
                'isActive', ChoiceType::class, array(
                'label'             => 'Status',
                'choices'           => array('Für Anmeldungen offen'     => true,
                                             'Keine Anmeldungen möglich' => false
                ),
                'expanded'          => true
            )
            )
            ->add(
                'isVisible', ChoiceType::class, array(
                'label'             => 'Sichtbarkeit',
                'choices'           => array('Aktiv'     => true,
                                             'Versteckt' => false
                ),
                'expanded'          => true
            )
            )
            ->add(
                'isAutoConfirm', ChoiceType::class, array(
                                   'label'    => 'Anmeldungs-Bestätigung',
                                   'choices'  => array(
                                       'Eingegangene Anmeldungen einzeln bestätigen' => false,
                                       'Alle Anmeldungen automatisch bestätigen'     => true

                                   ),
                                   'expanded' => true
                               )
            )
            ->add(
                'imageFile', VichImageType::class, array(
                'label'         => 'Poster',
                'required'      => false,
                'allow_delete'  => true,
                // not mandatory, default is true
                'download_link' => false,
                // not mandatory, default is true
            )
            )
            ->add(
                'hasConfirmationMessage', CheckboxType::class,
                array_merge($smartCheckbox, array('label' => 'Bestätigungs-Text'))
            )
            ->add(
                'confirmationMessage', TextareaType::class,
                array(
                    'label' => 'Bestätigungs-Text',
                    'attr'  => array('aria-describedby' => 'help-confirmation-message')
                )
            )
            ->add('save', SubmitType::class);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            if (!$form->get('hasStartTime')
                      ->getData()
            ) {
                $form->get('startTime')
                     ->setData(null);
            }
            if (!$form->get('hasEndDate')
                      ->getData()
            ) {
                $form->get('endDate')
                     ->setData(null);
            }
            if (!$form->get('hasEndTime')
                      ->getData()
            ) {
                $form->get('endTime')
                     ->setData(null);
            }
        }
        );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\Event',
            )
        );
    }
}
