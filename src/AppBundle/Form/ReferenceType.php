<?php

namespace AppBundle\Form;

use AppBundle\Entity\Conference;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReferenceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('author')
            ->add('title')
            ->add('inProc', ChoiceType::class, array("label"=>"Presented as","choices"=>["Unpublished"=>0,"Published"=>1]))
            ->add('conference', EntityType::class,array("choice_label"=>"getPlain","class"=>Conference::class))
            ->add("paperId", null, array("label"=>"Paper ID"))
            ->add('position', null, array("label"=>"pp."))
            ->add('isbn', null, array("label"=>"ISBN"))
            ->add('doi', null, array("label"=>"DOI"));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Reference'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_reference';
    }


}
