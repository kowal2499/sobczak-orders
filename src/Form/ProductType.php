<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;


class ProductType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'products',
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa',
                'constraints' => [
                    new NotBlank(['message' => 'Nazwa nie może być pusta'])
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ],
            ])
            ->add('factor', PercentType::class, [
                'label' => 'Współczynnik',
                'required' => true,
                'symbol' => false,
                'type' => 'fractional',
                'help' => 'Współczynnik dla premii. Wartość \'100\' oznacza współczynnik w wysokości \'1\'. Można podawać wartości wyższe niż 100.',
                'invalid_message' => 'Wartość musi liczbą większą od 0',
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'minMessage' => 'Wartość nie może być niższa od 0',
                    ]),
                    new NotBlank([
                        'message' => 'Wartość musi być liczbą większą od 0'
                    ])
                ],
                'attr' => [
                    'autocomplete' => 'off'
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Opis',
                'required' => false,
            ])
        ;
    }
}