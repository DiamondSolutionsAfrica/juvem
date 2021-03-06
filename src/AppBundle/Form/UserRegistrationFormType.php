<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class UserRegistrationFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options = array())
    {
        parent::buildForm($builder, $options);
        $builder
            ->add(
                'nameFirst', TextType::class, array('label'    => 'Vorname',
                                                    'required' => false
                           )
            )
            ->add('nameLast', TextType::class, array('label' => 'Nachname'))
            ->remove('username');  // we use email as the username
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_user_registration';
    }

}
