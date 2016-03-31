<?php

namespace AppBundle\Form;

use AppBundle\Entity\AcquisitionAttribute;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcquisitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'managementTitle',
            TextType::class,
            array(
                'label' => 'Titel (Intern)',
                'required' => false
            )
        )
                ->add(
                    'managementDescription',
                    TextType::class,
                    array(
                        'label' => 'Beschreibung (Intern)',
                        'required' => false
                    )
                )
                ->add(
                    'formTitle',
                    TextType::class,
                    array(
                        'label' => 'Titel (im Formular)',
                        'required' => false
                    )
                )
                ->add(
                    'formDescription',
                    TextType::class,
                    array(
                        'label' => 'Beschreibung (im Formular)',
                        'required' => false
                    )
                )
                ->add(
                    'fieldType',
                    ChoiceType::class,
                    array(
                        'label'             => 'Typ',
                        'choices'           => array(
                            AcquisitionAttribute::LABEL_FIELD_TEXT     => AcquisitionAttribute::TYPE_FIELD_TEXT,
                            AcquisitionAttribute::LABEL_FIELD_TEXTAREA => AcquisitionAttribute::TYPE_FIELD_TEXTAREA,
                            AcquisitionAttribute::LABEL_FIELD_CHOICE   => AcquisitionAttribute::TYPE_FIELD_CHOICE,
                        ),
                        'choices_as_values' => true,
                        'required'          => true

                    )
                )
                ->add(
                    'useAtParticipation',
                    CheckboxType::class,
                    array(
                        'label' => 'Je Anmeldung erfassen',
                        'required' => false
                    )
                )
                ->add(
                    'useAtParticipant',
                    CheckboxType::class,
                    array(
                        'label' => 'Je Teilnehmer erfassen',
                        'required' => false
                    )
                );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'AppBundle\Entity\AcquisitionAttribute',
            )
        );
    }
}
