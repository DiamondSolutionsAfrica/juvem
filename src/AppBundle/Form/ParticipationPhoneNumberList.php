<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationPhoneNumberList extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'phoneNumbers',
            CollectionType::class,
            array(
                'label'        => false,
                'entry_type'   => PhoneNumberType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'attr'         => array('aria-describedby' => 'help-info-phone-numbers')
            )
        );
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
