<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;


class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa',
                'constraints' => [
                    new NotBlank(['message' => 'Nazwa nie może być pusta'])
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('factor', PercentType::class, [
                'label' => 'Współczynnik',
                'required' => true,
                'symbol' => false,
                'type' => 'fractional',
                'help' => 'Współczynnik dla premii. Podaj wartość w przedziale od 0 do 100.',
                'invalid_message' => 'Wartość musi liczbą w przedziale 0-100',
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 1,
                        'minMessage' => 'Wartość nie może być niższa od 0',
                        'maxMessage' => 'Wartość nie może być wyższa od 100',
                    ]),
                    new NotBlank([
                        'message' => 'Wartość musi liczbą w przedziale 0-100'
                    ])
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Opis',
                'required' => false
            ])
        ;
    }
}