<?php

namespace App\Form;

use App\Entity\Theme;
use App\Form\Type\TagsInputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input input-text', 'placeholder' => 'Titre du sujet'], 
                'label' => 'Titre',
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'input input-textarea', 'placeholder' => 'A propos de quoi est ce sujet ?'], 
                'label' => 'Description',
                'required' => true
            ])
            ->add('private', CheckboxType::class, [
                'label' => 'Sujet privé',
                'help' => 'Vous seul pourrez voir ce sujet et y ajouter des liens.',
                'required' => false
            ])
            ->add('open', CheckboxType::class, [
                'label' => 'Ouvrir aux contributions',
                'help' => 'Les utilisateurs pourront ajouter des liens à ce sujet.',
                'required' => false
            ])
            ->add('approve', CheckboxType::class, [
                'label' => 'Approuver les contributions',
                'help' => 'Approuver les nouvelles contributions avant leur publication.',
                'required' => false
            ])
            ->add('tags', TagsInputType::class, [
                'attr' => ['class' => 'input input-text', 'placeholder' => 'tag, tag, ...'],
                'label' => 'Tags',
                'help' => 'Séparez les tags par une virgule',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Theme::class,
        ]);
    }
}
