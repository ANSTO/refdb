<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Search
 *
 * @ORM\Table(name="search_queries")
 * @ORM\Entity()
 */
class Search
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
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $paperId;

    /**
     * @var string
     * @ORM\Column(type="string", length=4000, nullable=true)
     */
    private $title;

    /**
     * @var ArrayCollection
     */
    private $author;

    /**
     * @var string
     * @ORM\Column(type="string", length=2000, nullable=true)
     */
    private $conference;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @var string
     * @ORM\Column(type="string", name="searchby_date", length=255, nullable=true)
     */
    private $date;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set paperId
     *
     * @param string $paperId
     *
     * @return Search
     */
    public function setPaperId($paperId)
    {
        $this->paperId = $paperId;

        return $this;
    }

    /**
     * Get paperId
     *
     * @return string
     */
    public function getPaperId()
    {
        return $this->paperId;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Search
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set author
     *
     * @param ArrayCollection $author
     *
     * @return Search
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return ArrayCollection
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set conference
     *
     * @param string $conference
     *
     * @return Search
     */
    public function setConference($conference)
    {
        $this->conference = $conference;

        return $this;
    }

    /**
     * Get conference
     *
     * @return string
     */
    public function getConference()
    {
        return $this->conference;
    }

    /**
     * Set location
     *
     * @param string $location
     *
     * @return Search
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set date
     *
     * @param string $date
     *
     * @return Search
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }
}
