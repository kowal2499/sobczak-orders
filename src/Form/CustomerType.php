<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa',
                'required' => true,
//                'constraints' => [
//                    new NotBlank(['message' => 'Nazwa nie może być pusta'])
//                ],
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('first_name', TextType::class, [
                'label' => 'Imię',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Nazwisko',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('street', TextType::class, [
                'label' => 'Ulica',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('street_number', TextType::class, [
                'label' => 'Nr ulicy',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('apartment_number', TextType::class, [
                'label' => 'Nr mieszkania',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'Miasto',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('postal_code', TextType::class, [
                'label' => 'Kod poczt',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('country', CountryType::class, [
                'label' => 'Kraj',
                'required' => false,
//                'choice_translation_locale' => 'pl',
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefon',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])

        ;
    }
}