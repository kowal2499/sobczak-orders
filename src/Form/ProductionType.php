<?php

namespace App\Form;

use App\Entity\Production;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('departmentSlug')
            ->add('dateStart', DateTimeType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm:ss'
            ])
            ->add('dateEnd', DateTimeType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm:ss'
            ])
            ->add('status')
            ->add('description')
            ->add('title')
            ->add('statusLogs', CollectionType::class, [
                'entry_type' => StatusLogType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Production::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
