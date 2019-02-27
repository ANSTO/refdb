<?php


namespace AppBundle\Service;

use AppBundle\Entity\Reference;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FavouriteService {
    private $manager;
    private $requestStack;
    private $tokenStorage;

    public function __construct(ObjectManager $objectManager, RequestStack $requestStack, TokenStorageInterface $tokenStorage)
    {
        $this->requestStack = $requestStack;
        $this->manager = $objectManager;
        $this->tokenStorage = $tokenStorage;
    }

    public function getSession() {
        return $this->requestStack->getCurrentRequest()->getSession();
    }

    protected function getUser()
    {
        if (!$this->tokenStorage) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }

    public function getFavourites() {
        $favourites = [];
        if ($this->getUser()) {

        } else {
            $session = $this->getSession();
            if (!$session->has("favourites")) {
                $favourites = [];
            } else {
                $favourites = $session->get("favourites");
            }
        }
        return array_values($favourites);
    }

    public function check(Reference $reference) {
        $favourites = $this->getFavourites();
        return in_array($reference->getId(), $favourites);
    }

    public function toggle(Reference $reference) {
        $added = false;
        $favourites = [];
        if ($this->getUser()) {

        } else {

            $session = $this->getSession();

            if (!$session->has("favourites")) {
                $favourites = [];
            } else {
                $favourites = $session->get("favourites");
            }
            if (in_array($reference->getId(), $favourites)) {
                $added = false;
                $favourites = array_filter($favourites, function($item) use ($reference) { return $item !== $reference->getId(); });
            } else {
                $added = true;
                $favourites[] = $reference->getId();
            }

            $session->set("favourites", $favourites);
        }

        return ["favourites"=> array_values($favourites), "added"=>$added];
    }
}