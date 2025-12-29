<?php

namespace App\Form;

use App\DTO\BurgerSearchFormDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BurgerSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du burger',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par nom...',
                    'class' => 'form-control'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'placeholder' => 'Tous les statuts',
                'choices' => [
                    'Disponible' => 'disponible',
                    'Indisponible' => 'indisponible',
                    'Archivé' => 'archive',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('prixMin', NumberType::class, [
                'label' => 'Prix minimum',
                'required' => false,
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-control',
                    'step' => '0.01'
                ]
            ])
            ->add('prixMax', NumberType::class, [
                'label' => 'Prix maximum',
                'required' => false,
                'attr' => [
                    'placeholder' => '10000',
                    'class' => 'form-control',
                    'step' => '0.01'
                ]
            ])
            ->add('rechercher', SubmitType::class, [
                'label' => 'Rechercher',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BurgerSearchFormDto::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
