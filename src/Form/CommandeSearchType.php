<?php

namespace App\Form;

use App\DTO\CommandeSearchFormDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class CommandeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero', TextType::class, [
                'label' => 'Numéro commande',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par numéro...',
                    'class' => 'filter-input'
                ],
            ])

            ->add('clientId', IntegerType::class, [
                'label' => 'ID Client',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ID du client...',
                    'class' => 'filter-input'
                ],
            ])

            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'placeholder' => '-- Tous les statuts --',
                'choices' => [
                    'En attente' => 'VALIDEE',
                    'En préparation' => 'EN_COURS', 
                    'Prête' => 'PRETE',
                    'En livraison' => 'EN_LIVRAISON',
                    'Livrée' => 'LIVREE',
                    'Terminée' => 'TERMINEE',
                    'Annulée' => 'ANNULEE',
                ],
                'attr' => ['class' => 'filter-input'],
            ])

            ->add('typeConsommation', ChoiceType::class, [
                'label' => 'Mode consommation',
                'required' => false,
                'placeholder' => '-- Tous les modes --',
                'choices' => [
                    'Sur place' => 'SUR_PLACE',
                    'À emporter' => 'A_EMPORTER',
                    'Livraison' => 'LIVRAISON',
                ],
                'attr' => ['class' => 'filter-input'],
            ])

            ->add('dateDebut', DateType::class, [
                'label' => 'Date début',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'filter-input',
                    'max' => (new \DateTime())->format('Y-m-d'),
                ],
            ])

            ->add('dateFin', DateType::class, [
                'label' => 'Date fin',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'filter-input',
                    'max' => (new \DateTime())->format('Y-m-d'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommandeSearchFormDto::class,
            'method' => 'GET',
            'csrf_protection' => false,
            'attr' => [
                'class' => 'search-form-filters'
            ],
        ]);
    }
}