<?php


namespace App\Form;

use App\Entity\Agreement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgreementsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', CustomerType::class)
            ->add('products', CollectionType::class, [
                'entry_type'
            ])
            ->add('orderNumber')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Agreement::class,
            'csrf_protection' => false,
        ]);
    }

}