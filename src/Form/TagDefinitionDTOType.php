<?php
/** @author: Roman Kowalski */

namespace App\Form;

use App\DTO\TagDefinitionDTO;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class TagDefinitionDTOType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => TagDefinitionDTO::class,
            'empty_data' => function(FormInterface $form) {
                return new TagDefinitionDTO(
                    $form->get('name')->getData() ?? '',
                    $form->get('module')->getData() ?? ''
                );
            }
        ]);
    }
}