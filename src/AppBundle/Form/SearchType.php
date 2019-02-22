<?php

namespace AppBundle\Form;

use AppBundle\Entity\Search;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('conference', null, ['label'=>"Conference Name"])
            ->add('date', null,["label"=>"Conference Date"])
            ->add('location', null, ["label"=>"Conference Location"])
            ->add('paperId', null, array("label"=>"Paper ID"))
            ->add('author')
            ->add('title', null, ["label"=>"Paper Title"])

            ;
    }/**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Search::class,
            "csrf_protection" => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_search';
    }


}
