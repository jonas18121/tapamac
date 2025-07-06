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
            /** @var FormBuilderInterface $builder */
            $builder = $this->getSituationFamily($builder);

            /** @var FormBuilderInterface $builder */
            $builder = $this->getTypeOfContract($builder);
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

    // Custom Methode

    /**
     * Affiche le champ select situation familiale en fonction du choix du champ select genre
     */
    public function getSituationFamily(FormBuilderInterface $builder): FormBuilderInterface
    {
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
        $formUpdateGender = function (FormEvent $event) : void {
            /** @var string $choiceGender */
            $choiceGender = $event->getData();
            /** @var FormInterface $form */
            $form = $event->getForm()->getParent();

            if (
                null !== $choiceGender 
                && 'non_selection' !== $choiceGender
            ) {
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
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formUpdateGender) {
                /** @var string|null $gender */
                $gender = $event->getData();
                if (null !== $gender) {
                    $formUpdateGender($event);
                }
            }
        );

        // Ajoute la liste de situation dans le champ select situation après le clique sur submit,
        // afin de lister les options dans le selecteur situation, 
        // pour que symfony puisse accepter le choix lors de la validation
        $builder->get('gender')->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formUpdateGender) {
                /** @var string|null $gender */
                $gender = $event->getForm()->getData();
                if (null !== $gender) {
                    $formUpdateGender($event);
                }
            }
        );

        return $builder;
    }

    /**
     * Affiche le champ select Type de contrat en fonction du choix du champ select Situation professionnel
     * 
     * Select Situation professionnel (Employé, Chômage, Retraité) et Type de contrat (CDI, CDD, Interim)
     * Le select Type de contrat s'affiche uniquement si la valeur de la Situation professionel est Employé
     */
    public function getTypeOfContract(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builder
            ->add('professional', ChoiceType::class, [
                'choices' => [
                    'Sélectionner un genre' => 'non_selection',
                    'Employé' => 'employe',
                    'Chômeur' => 'chomeur',
                    'Retraité' => 'retraite',
                    'Autres' => 'autres',
                ],
                'required' => false
            ])
        ;

        // Permet à Symfony de connaitre les options du champ select "Type de contrat" qui ont été modifier dynamiquement
        // Car si Symfony ne les connaient pas, il ne va pas les considérer comme valide
        $formUpdateProfessional = function (FormEvent $event) {
            /** @var string $choiceProfessional */
            $choiceProfessional = $event->getData();
            /** @var FormInterface $form */
            $form = $event->getForm()->getParent();

            if (
                null !== $choiceProfessional 
                && 'non_selection' !== $choiceProfessional
            ) {
                $typeOfContracts = [
                    'Choisir un genre avant de choisir une situation' => '',
                    'Interim' => 'interim',
                    'CDI' => 'cdi',
                    'CDD' => 'cdd'
                ];

                $form->add('typeOfContract', ChoiceType::class, [
                    'choices' => $typeOfContracts,
                    'required' => false
                ]);
            }
        };

        // Ajoute la liste de "Type de contrat" dans le champ select typeOfContract après le chargement de la page, si un gender à été choisit,
        // afin de lister les options dans le selecteur typeOfContract, 
        // pour que symfony puisse accepter le choix lors de la validation
        $builder->get('professional')->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) use ($formUpdateProfessional) {
                /** @var string|null $professional */
                $professional = $event->getData();

                if (
                    null !== $professional
                    && 'employe' === $professional
                ) {
                    $formUpdateProfessional($event);
                }
            }
        );

        // Ajoute la liste de "Type de contrat" dans le champ select typeOfContract après le clique sur submit,
        // afin de lister les options dans le selecteur typeOfContract, 
        // pour que symfony puisse accepter le choix lors de la validation
        $builder->get('professional')->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formUpdateProfessional) {
                /** @var string|null professional */
                $professional = $event->getData();
                
                if (
                    null !== $professional
                    && 'employe' === $professional
                ) {
                    $formUpdateProfessional($event);
                }
            }
        );

        return $builder;
    }
}
