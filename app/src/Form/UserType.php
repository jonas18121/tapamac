<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champs de base
        $builder
            ->add('email')
            ->add('password')
            ->add('firstName')
            ->add('lastName')
            ->add('phoneNumber')
        ;

        // Champs spécifique à la partie Frontend
        if (
            array_key_exists('window_user',$options) 
            && isset($options['window_user']) 
            && 'frontend' === $options['window_user']
        ) {
            $builder->add('isVerified', CheckboxType::class);
        }

        // Champs spécifique à la partie Backend
        if (
            array_key_exists('window_user',$options) 
            && isset($options['window_user']) 
            && 'backend' === $options['window_user']
        ) {
            $builder->add('isVerified', CheckboxType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'window_user' => null
        ]);
    }
}
