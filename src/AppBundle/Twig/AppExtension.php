<?php

// src/AppBundle/Twig/AppExtension.php
namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private $twig;
    public function __construct(\Twig_Environment $twig)
    {
         $this->twig = $twig;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('latin', [$this, 'latinReplace'], ['is_safe' => ['html']]),
        ];
    }



    public function latinReplace($text)
    {
        $text = strip_tags($text, "<em><sup><sub><br>");
        $text = str_replace(" et al.", " <em>et al.</em>", $text);
        return $text;
    }
}