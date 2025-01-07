<?php

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Customers2Users;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Customers2UsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [ 'class' => Customer::class ])
            ->add('user', EntityType::class, [ 'class' => User::class ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customers2Users::class,
            'allow_extra_fields' => false
        ]);
    }
}