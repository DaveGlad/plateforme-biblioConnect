<?php

namespace App\Form;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Language;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un titre',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('isbn', TextType::class, [
                'label' => 'ISBN',
                'required' => false,
            ])
            ->add('publishYear', IntegerType::class, [
                'label' => 'Année de publication',
                'constraints' => [
                    new Positive([
                        'message' => 'L\'année doit être un nombre positif',
                    ]),
                ],
            ])
            ->add('totalCopies', IntegerType::class, [
                'label' => 'Nombre total d\'exemplaires',
                'constraints' => [
                    new Positive([
                        'message' => 'Le nombre d\'exemplaires doit être un nombre positif',
                    ]),
                ],
            ])
            ->add('availableCopies', IntegerType::class, [
                'label' => 'Nombre d\'exemplaires disponibles',
                'constraints' => [
                    new Positive([
                        'message' => 'Le nombre d\'exemplaires disponibles doit être un nombre positif',
                    ]),
                ],
            ])
            ->add('author', EntityType::class, [
                'class' => Author::class,
                'choice_label' => function (Author $author) {
                    return $author->getFullName();
                },
                'label' => 'Auteur',
                'placeholder' => 'Sélectionner un auteur',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un auteur',
                    ]),
                ],
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Catégories',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner au moins une catégorie',
                    ]),
                ],
            ])
            ->add('language', EntityType::class, [
                'class' => Language::class,
                'choice_label' => 'name',
                'label' => 'Langue',
                'placeholder' => 'Sélectionner une langue',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une langue',
                    ]),
                ],
            ])
            ->add('coverImage', FileType::class, [
                'label' => 'Image de couverture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG)',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}