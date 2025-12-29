<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BurgerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du burger',
                'attr' => [
                    'placeholder' => 'Ex: Brasil Classic',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom du burger est obligatoire.'),
                    new Assert\Length(min: 3, minMessage: 'Le nom doit contenir au moins 3 caractères.')
                ]
            ])
            ->add('prix', TextType::class, [ // Changed from MoneyType to TextType for demo
                'label' => 'Prix (FCFA)',
                'attr' => [
                    'placeholder' => '2500',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le prix est obligatoire.'),
                    new Assert\Regex(pattern: '/^\d+$/', message: 'Le prix doit être un nombre.')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Décrivez les ingrédients et caractéristiques...',
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du burger',
                'mapped' => false, // Non persisté
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WebP).',
                    ])
                ],
                'help' => 'Taille max: 2MB. Formats: JPEG, PNG, WebP'
            ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Disponible à la vente',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'help' => 'Décochez si le burger est temporairement indisponible'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Removed data_class to work with demo objects
        ]);
    }
}
