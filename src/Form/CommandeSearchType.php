<?php

namespace App\Form;

use App\DTO\CommandeSearchFormDto;
use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

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
                    'class' => 'filter-input',
                    'autocomplete' => 'off',
                ],
            ])

            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getNomComplet() ?: $client->getEmail();
                },
                'required' => false,
                'placeholder' => 'Rechercher par client',
                'attr' => ['class' => 'filter-input'],
            ])

            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'placeholder' => '-- Statut --',
                'choices' => [
                    'En attente' => 'pending',
                    'En préparation' => 'preparing', 
                    'En livraison' => 'delivering',
                    'Terminées' => 'completed',
                    'Annulées' => 'cancelled',
                ],
                'expanded' => false,
                'multiple' => false,
                'attr' => ['class' => 'filter-input'],
                'choice_translation_domain' => false,
            ])

            ->add('typeConsommation', ChoiceType::class, [
                'label' => 'Mode consommation',
                'required' => false,
                'placeholder' => '-- Mode de consommation --',
                'choices' => [
                    'Sur place' => 'surplace',
                    'À emporter' => 'emporter',
                    'Livraison' => 'livraison',
                ],
                'expanded' => false,
                'multiple' => false,
                'attr' => ['class' => 'filter-input'],
                'choice_translation_domain' => false,
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
                'data-turbo' => false,
            ],
        ]);
    }
}
