<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConferenceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name')
            ->add('code', null, array("label"=>"Conference Code (format ACRONYM'YY)"))
            ->add('year',null,array(
                "label"=>"Month Year (format May 2018, Jun.-Jul. 2019 or Jun. 2019)"))
            ->add('location', null, array("label"=>"City, State (if USA), Country"))
            ->add("useDoi", ChoiceType::class, ["choices"=>[ "No"=>0,"Yes"=>1]])
            ->add("doiCode", TextType::class, ["label"=>"Doi Code: IPAC2017 (from 10.18429/JACoW-THISCODE-PAPERID)", "required"=>false])
            ->add("isPublished", CheckboxType::class, ["label"=>"Is this conference proceedings published?", "required"=>false])
            ->add("importUrl", UrlType::class, ["label"=>"URL to CSV Import", "required"=>false]);
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Conference'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_conference';
    }


}
