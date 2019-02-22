<?php

namespace AppBundle\Form;

use AppBundle\Entity\Author;
use AppBundle\Entity\Search;
use AppBundle\Form\Type\TagsAsInputType;
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
            ->add('conference', null, ['label'=>"Conference Code / Name", "attr"=>["class"=>"conference-typeahead","autocomplete"=>"off"]])
            ->add('date', null,["label"=>"Conference Date", "attr"=>["class"=>"conference-date-typeahead","autocomplete"=>"off"]])
            ->add('location', null, ["label"=>"Conference Location", "attr"=>["class"=>"conference-location-typeahead","autocomplete"=>"off"]])
            ->add('paperId', null, array("label"=>"Paper ID"))
            ->add('author', TagsAsInputType::class, [
                "entity_class"=> Author::class,
                "data_source" => "author_search",
                "label"=> "Author/s"])
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
