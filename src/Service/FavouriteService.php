<?php


namespace App\Service;

use App\Entity\Favourite;
use App\Entity\Reference;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FavouriteService {
    private $manager;
    private $requestStack;
    private $tokenStorage;

    public function __construct(EntityManagerInterface $objectManager, RequestStack $requestStack, TokenStorageInterface $tokenStorage)
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
        $session = $this->getSession();
        if (!$session->has("favourites")) {
            $favourites = [];
        } else {
            $favourites = $session->get("favourites");
        }

        if ($this->getUser() && $this->getUser() instanceof User) {
            /** @var User $user */
            $user = $this->getUser();
            foreach ($user->getFavourites() as $fav) {
                if (!in_array($fav->getReference()->getId(),$favourites)) {
                    $favourites[] = $fav->getReference()->getId();
                }

            }
        }
        return array_values($favourites);
    }

    public function check(Reference $reference) {
        $favourites = $this->getFavourites();
        return in_array($reference->getId(), $favourites);
    }

    private function save($favourites) {
        if ($this->getUser() && $this->getUser() instanceof User) {
            /** @var User $user */
            $user = $this->getUser();
            $dbFavs = $user->getFavourites();

            /** @var Favourite $dbFav */
            foreach ($dbFavs as $dbFav) {
                $found = false;
                foreach ($favourites as $fav) {
                    if ($fav == $dbFav->getReference()->getId()) {
                        $found = true;
                        break;
                    }
                }
                if ($found == false) {
                    $this->manager->remove($dbFav);
                }
            }

            foreach ($favourites as $fav) {
                $found = false;
                foreach ($dbFavs as $dbFav) {
                    if ($fav == $dbFav->getReference()->getId()) {
                        $found = true;
                        break;
                    }
                }
                if ($found == false) {
                    $newFav = new Favourite();
                    $newFav->setUser($user);
                    $reference = $this->manager->getRepository(Reference::class)->find($fav);
                    $newFav->setReference($reference);
                    $reference->setHits(($reference->getHits() ?? 0) + 1);
                    $this->manager->persist($newFav);
                }
            }

            $this->manager->flush();
        }
        $this->getSession()->set("favourites", $favourites);
    }

    public function toggle(Reference $reference) {
        $added = false;
        $favourites = $this->getFavourites();
        if (in_array($reference->getId(), $favourites)) {
            $added = false;
            $favourites = array_filter($favourites, function($item) use ($reference) { return $item !== $reference->getId(); });
        } else {
            $added = true;
            $favourites[] = $reference->getId();
        }

        $this->save($favourites);

        return ["favourites"=> array_values($favourites), "added"=>$added];
    }
}
