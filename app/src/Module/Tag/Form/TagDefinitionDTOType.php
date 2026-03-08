<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\Form;

use App\Module\Tag\DTO\TagDefinitionDTO;
use App\Form\BaseType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagDefinitionDTOType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('module', TextType::class, [
                'required' => true,
            ])
            ->add('icon', TextType::class)
            ->add('color', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => TagDefinitionDTO::class,
            'empty_data' => function (FormInterface $form) {
                return new TagDefinitionDTO(
                    $form->get('name')->getData() ?? '',
                    $form->get('module')->getData() ?? ''
                );
            },
        ]);
    }
}
