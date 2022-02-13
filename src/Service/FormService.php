<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class FormService
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getSession() {
        return $this->requestStack->getCurrentRequest()->getSession();
    }

    public function setShort() {
        return $this->getSession()->set("form","short");
    }

    public function setLong() {
        return $this->getSession()->set("form","long");
    }

    public function toggleForm() {
        if ($this->getForm() == "long") {
            $this->setShort();
        } else {
            $this->setLong();
        }
    }

    public function getForm() {
        if (!$this->getSession()->has("form")) {
            return "short";
        }
        else {
            return $this->getSession()->get("form");
        }
    }
}
