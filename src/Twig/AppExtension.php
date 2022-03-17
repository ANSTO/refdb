<?php

namespace App\Twig;

use App\Service\CurrentConferenceService;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private $twig;
    private $currentConferenceService;
    public function __construct(Environment $twig, CurrentConferenceService $currentConferenceService)
    {
        $this->currentConferenceService = $currentConferenceService;
        $this->twig = $twig;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('latin', [$this, 'latinReplace'], ['is_safe' => ['html']]),
        ];
    }

    private function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    public function latinReplace($text)
    {
        if ($this->currentConferenceService->hasCurrent()) {
            $current = $this->currentConferenceService->getCurrent();
            // confirm its not just a conference text
            if ($current !== null && $current->__toString() !== $text && $this->endsWith($text, ".") !== false) {
                if (strpos($text, $current->getCode()) !== false) {
                    $text = str_replace(", unpublished", ", this conference", $text);
                }
            }
        }
        $text = strip_tags($text, "<em><sup><sub><br>");
        $text = str_replace(" et al.", " <em>et al.</em>", $text);
        return $text;
    }
}
