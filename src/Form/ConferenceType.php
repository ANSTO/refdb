<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class ConferenceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('code', null, array("label"=>"Conference Code (format ACRONYM'YY)"))
            ->add('series', null, ["label"=>"Series (e.g. International Beam Instrumentation Conference)", "required"=>false])
            ->add('seriesNumber', null, ["label"=>"Series Number (e.g. 12)", "required"=>false])
            ->add('conferenceStart', null, [
                'widget' => 'single_text',
                "label"=>"First day of conference", 
                'html5' => false,
                'attr' => ['class' => 'js-datepicker conference-date conference-date-start'],
                'format' => 'dd/MM/yyyy',
                "required"=>false])
            ->add('conferenceEnd', null, [
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'js-datepicker conference-date conference-date-end'],
                'format' => 'dd/MM/yyyy',
                "label"=>"Last day of conference",
                "required"=>false])
            ->add('year',null,array(
                "label"=>"Month Year of Conference (format May 2018, Jun.-Jul. 2019 or Jun. 2019)",
                'attr' => ['class' => 'conference-date-formatted'],))
            ->add('location', null, array("label"=>"City, State (if USA), Country"))
            ->add("useDoi", ChoiceType::class, ["choices"=>[ "No"=>0,"Yes"=>1]])
            ->add("doiCode", TextType::class, ["label"=>"Doi Code: IPAC2017 (from 10.18429/JACoW-THISCODE-PAPERID)", "required"=>false])
            ->add("isPublished", CheckboxType::class, ["label"=>"Is this conference proceedings published?", "required"=>false])
            ->add('pubMonth', TextType::class, ["required"=>false,"label"=>"Publication Month","constraints"=>new Regex("/^[0-9]{1,2}$/")])
            ->add('pubYear', TextType::class, ["required"=>false,"label"=>"Publication Year","constraints"=>new Regex("/^[0-9]{4}$/")])
            ->add('issn', TextType::class, [
                "required"=>false,"label"=>"ISSN",
                "attr" => [
                    "data-inputmask-mask" => "9999-9999",
                    "placeholder" => '____-____'
                ],
                "constraints"=>new Regex("/^[0-9]{8}$/")
            ])
            ->add('isbn', TextType::class, [
                "required"=>false,
                "label"=>"ISBN",
                'attr' => [
                    "data-inputmask-mask" => "999-9-99-999999-9",
                    "placeholder' => '___-_-__-______-_"
                ],
                "constraints"=>new Regex("/^[0-9]{13}$/")])
        
            ->add("baseUrl", UrlType::class, ["label"=>"Website URL", "required"=>false])
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
