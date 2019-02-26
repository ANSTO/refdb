<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Conference
 *
 * @ORM\Table(name="conference",indexes={@ORM\Index(name="conference_code_idx", columns={"code"})}))
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ConferenceRepository")
 */
class Conference implements \JsonSerializable
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
     * Long version of a conference name.
     * @ORM\Column(name="name", type="string", length=4000, nullable=true)
     */
    private $name;

    /**
     * @var string
     * eg. IPAC'18
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * This is the date of the conference, Eg. May 2018
     * @var string
     * @Assert\Regex("/^((?!May. )(May|[A-Z]{1}[a-z]{2}\.)(\-(?!May. )(May|[A-Z]{1}[a-z]{2}\.))?) [0-9]{4}$/", message="Please the correct format the date held in the format MMM YYYY")
     * @ORM\Column(name="year", type="string", length=255, nullable=true)
     */
    private $year;

    /**
     * Conference component of the DOIs
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $doiCode;

    /**
     * Enable DOIs or not.
     * @ORM\Column(type="boolean", nullable=true)
     * @var boolean
     */
    private $useDoi;

    /**
     * Location of the conference, eg. Sydney, Australia
     * @var string
     * @Assert\Regex("/^([A-Za-zàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ '\-]+, (?!USA)[A-Za-zàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ '\-]+|[A-Za-zàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ '\-]+, [A-Za-zàèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ '\-]+, USA)$/")
     * @ORM\Column(name="location", type="string", length=2000)
     */
    private $location;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Reference", mappedBy="conference")
     * @var ArrayCollection
     */
    private $references;

    /**
     * Status of the conference proceedings
     *
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isPublished;

    /**
     * Automatic import URL for unpublished conferences
     * @var string
     *
     * @ORM\Column(type="string", length=2000, nullable=true)
     */
    private $importUrl;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Conference
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Conference
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set year
     *
     * @param string $year
     *
     * @return Conference
     */
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return string
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set location
     *
     * @param string $location
     *
     * @return Conference
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
     * @return ArrayCollection
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * @param ArrayCollection $references
     */
    public function setReferences($references)
    {
        $this->references = $references;
    }

    public function __toString()
    {
        if ($this->getName() !== null) {
            return $this->getName() . " (" . $this->getCode() . ")";
        } else {
            return $this->getCode();
        }
    }

    public function getPlain() {
        return strip_tags($this->__toString());
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->references = new ArrayCollection();
    }

    /**
     * Add reference
     *
     * @param Reference $reference
     *
     * @return Conference
     */
    public function addReference(Reference $reference)
    {
        $this->references[] = $reference;

        return $this;
    }

    /**
     * Remove reference
     *
     * @param Reference $reference
     */
    public function removeReference(Reference $reference)
    {
        $this->references->removeElement($reference);
    }

    /**
     * @return bool
     */
    public function isUseDoi()
    {
        return $this->useDoi;
    }

    /**
     * @param bool $useDoi
     */
    public function setUseDoi($useDoi)
    {
        $this->useDoi = $useDoi;
    }

    /**
     * @return string
     */
    public function getDoiCode()
    {
        return $this->doiCode;
    }

    /**
     * @param string $doiCode
     */
    public function setDoiCode($doiCode)
    {
        $this->doiCode = $doiCode;
    }

    /**
     * @return string
     */
    public function getImportUrl()
    {
        return $this->importUrl;
    }

    /**
     * @param string $importUrl
     */
    public function setImportUrl($importUrl)
    {
        $this->importUrl = $importUrl;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->isPublished;
    }

    /**
     * @param bool $isPublished
     */
    public function setIsPublished($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    public function jsonSerialize()
    {
        return ["name"=> $this->getName(),
            "code" => $this->getCode(),
            "location" => $this->getLocation(),
            "date" => $this->getYear(),
            "id"=>$this->getId()];
    }
}
