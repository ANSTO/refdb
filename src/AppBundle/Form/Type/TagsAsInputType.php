<?php

namespace AppBundle\Form\Type;

use AppBundle\Form\DataTransformer\TagTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagsAsInputType extends AbstractType {

    private $manager;

    public function __construct(ObjectManager $manager) {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder->addViewTransformer(new TagTransformer($this->manager, $options['data_class']));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'data_class' => null,
            ));

        $resolver
            ->setRequired(array(
                'data_class' => null,
            ));
    }

    public function getParent() {
        return TextType::class;
    }

}