<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Favourite", mappedBy="user", cascade={"remove"})
     */
    private $favourites;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    /**
     * @return ArrayCollection
     */
    public function getFavourites()
    {
        return $this->favourites;
    }

    /**
     * @param ArrayCollection $favourites
     */
    public function setFavourites($favourites)
    {
        $this->favourites = $favourites;
    }
}