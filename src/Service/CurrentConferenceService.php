<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace App\Service;


use App\Entity\Conference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class CurrentConferenceService
{
    private $manager;
    private $requestStack;
    private $default;

    public function __construct(EntityManagerInterface $objectManager, RequestStack $requestStack, $defaultConference)
    {
        $this->requestStack = $requestStack;
        $this->manager = $objectManager;
        $this->default = false;

        // default to conference code
        $conference = $this->manager->getRepository(Conference::class)->findOneBy([ "code"=> $defaultConference]);
        if ($conference) {
            $this->default = $conference->getId();
        }
    }

    public function getSession() {
        return $this->requestStack->getCurrentRequest()->getSession();
    }

    public function dismiss() {
        return $this->getSession()->set("understand","1");
    }

    public function understand() {
        return $this->getSession()->has("understand");
    }

    public function hasCurrent() {
        return $this->getSession()->get("current", $this->default);
    }

    public function clearCurrent() {
        $session = $this->getSession();
        $session->set("current", false);
    }

    public function setCurrent(Conference $conference) {
        $session = $this->getSession();
        $session->set("current", $conference->getId());
    }

    public function getCurrent() {
        /** @var Conference $conference */
        $conference = $this->manager
            ->getRepository(Conference::class)
            ->find($this->getSession()->get("current", $this->default));


        if ($conference !== null && $conference->isPublished() == false) {
            return $conference;
        }
        return null;
    }

}
