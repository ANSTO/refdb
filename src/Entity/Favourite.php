<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Favourite
 *
 * @ORM\Table(name="favourite")
 * @ORM\Entity(repositoryClass="App\Repository\FavouriteRepository")
 */
class Favourite implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Reference     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Reference", inversedBy="favourites")
     * @ORM\JoinColumn(nullable=false)
     */
    private $reference;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="favourites")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set reference.
     *
     * @param Reference|null $reference
     *
     * @return Favourite
     */
    public function setReference(Reference $reference = null)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference.
     *
     * @return Reference|null
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set user.
     *
     * @param \App\Entity\User|null $user
     *
     * @return Favourite
     */
    public function setUser($user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \App\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->getReference()->getId();
    }
}
