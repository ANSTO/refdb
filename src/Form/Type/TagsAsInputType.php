<?php

namespace App\Form\Type;

use App\Form\DataTransformer\TagTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagsAsInputType extends AbstractType {

    private $manager;

    public function __construct(EntityManagerInterface $manager) {
        $this->manager = $manager;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars["attr"]["data-source"] = $options['data_source'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->addViewTransformer(new TagTransformer($this->manager, $options['entity_class']));
    }

    public function getBlockPrefix()
    {
        return "entity_tags";
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                "required"=>false,
                'data_source' => null,
                'entity_class' => null,
            ));
    }

    public function getParent() {
        return TextType::class;
    }

}
