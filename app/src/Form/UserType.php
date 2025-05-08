<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ===== Champs de base ===== //
        $builder
            ->add('email')
            ->add('firstName')
            ->add('lastName')
            ->add('phoneNumber')
        ;

        // ===== Champs password ===== //
        if (
            array_key_exists('use_password',$options) 
            && isset($options['use_password']) 
            && 'use_password' === $options['use_password']
        ) {
            $builder->add('password');
        }

        // ===== Champs spécifique à la partie Frontend ===== //
        if (
            array_key_exists('window_user',$options) 
            && isset($options['window_user']) 
            && 'frontend' === $options['window_user']
        ) {
            $builder
                ->add('gender', ChoiceType::class, [
                    'choices' => [
                        'Sélectionner un genre' => 'non_selection',
                        'Homme' => 'homme',
                        'Femme' => 'femme',
                        'Non binaire' => 'non_binaire',
                    ],
                    'required' => false
                ])
                ->add('situation', ChoiceType::class, [
                    'placeholder' => 'Sélectionner une situation',
                    'choices' => [],
                    'required' => false
                ])
            ;

            // Permet à Symfony de connaitre les options du champ select situation qui ont été modifier dynamiquement
            // Car si Symfony ne les connaient pas, il ne va pas les considérer comme valide
            $formUpdateGender = function (?FormInterface $form) {
                if (null !== $form) {
                    $situations = [
                        'Choisir un genre avant de choisir une situation' => '',
                        'Polyamour' => 'polyamour',
                        'Célibataire' => 'celibataire',
                        'Concubinage' => 'concubinage',
                        'pacsé' => 'pacse',
                        'Marié' => 'marie',
                    ];

                    $form->add('situation', ChoiceType::class, [
                        'choices' => $situations,
                        'required' => false
                    ]);
                }
            };

            // Ajoute la liste de situation dans le champ select situation après le chargement de la page, si un gender à été choisit,
            // afin de lister les options dans le selecteur situation, 
            // pour que symfony puisse accepter le choix lors de la validation
            $builder->get('gender')->addEventListener(
                FormEvents::POST_SET_DATA,
                function (FormEvent $event) use ($formUpdateGender) {
                    /** @var string|null */
                    $gender = $event->getForm()->getData();
                    if (null !== $gender) {
                        $formUpdateGender($event->getForm()->getParent());
                    }
                }
            );

            // Ajoute la liste de situation dans le champ select situation après le clique sur submit,
            // afin de lister les options dans le selecteur situation, 
            // pour que symfony puisse accepter le choix lors de la validation
            $builder->get('gender')->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($formUpdateGender) {
                    /** @var string|null */
                    $gender = $event->getForm()->getData();
                    if (null !== $gender) {
                        $formUpdateGender($event->getForm()->getParent());
                    }
                }
            );
        }

        // ===== Champs spécifique à la partie Backend ===== //
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
            'window_user' => null,
            'use_password' => null
        ]);
    }
}
