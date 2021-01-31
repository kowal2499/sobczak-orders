<?php

namespace App\Form;

use App\Entity\AgreementLine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgreementLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productions', CollectionType::class, [
                'entry_type' => ProductionType::class,
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('status', IntegerType::class)
            ->add('description', TextType::class)
            ->add('factor', NumberType::class, [
                'scale' => 2
            ])
            ->add('confirmedDate', DateTimeType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd HH:mm:ss'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => AgreementLine::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
