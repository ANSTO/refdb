<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Conference
 *
 * @ORM\Table(name="conference")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ConferenceRepository")
 */
class Conference
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
     *
     * @ORM\Column(name="name", type="string", length=4000, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var string
     * @Assert\Regex("/^((?!May. )(May|[A-Z]{1}[a-z]{2}\.)(\-(?!May. )(May|[A-Z]{1}[a-z]{2}\.))?) [0-9]{4}$/", message="Please the correct format the date held in the format MMM YYYY")
     * @ORM\Column(name="year", type="string", length=255, nullable=true)
     */
    private $year;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $doiCode;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @var boolean
     */
    private $useDoi;

    /**
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
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $publicSubmission;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Conference", inversedBy="replacements")
     */
    private $replaces;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Conference", mappedBy="replaces")
     */
    private $replacements;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isPublished;

    /**
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
        $this->references = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add reference
     *
     * @param \AppBundle\Entity\Reference $reference
     *
     * @return Conference
     */
    public function addReference(\AppBundle\Entity\Reference $reference)
    {
        $this->references[] = $reference;

        return $this;
    }

    /**
     * Remove reference
     *
     * @param \AppBundle\Entity\Reference $reference
     */
    public function removeReference(\AppBundle\Entity\Reference $reference)
    {
        $this->references->removeElement($reference);
    }

    /**
     * Set publicSubmission
     *
     * @param boolean $publicSubmission
     *
     * @return Conference
     */
    public function setPublicSubmission($publicSubmission)
    {
        $this->publicSubmission = $publicSubmission;

        return $this;
    }

    /**
     * Get publicSubmission
     *
     * @return boolean
     */
    public function getPublicSubmission()
    {
        return $this->publicSubmission;
    }

    /**
     * Set replaces
     *
     * @param \AppBundle\Entity\Conference $replaces
     *
     * @return Conference
     */
    public function setReplaces(\AppBundle\Entity\Conference $replaces = null)
    {
        $this->replaces = $replaces;

        return $this;
    }

    /**
     * Get replaces
     *
     * @return \AppBundle\Entity\Conference
     */
    public function getReplaces()
    {
        return $this->replaces;
    }

    /**
     * Add replacement
     *
     * @param \AppBundle\Entity\Conference $replacement
     *
     * @return Conference
     */
    public function addReplacement(\AppBundle\Entity\Conference $replacement)
    {
        $this->replacements[] = $replacement;

        return $this;
    }

    /**
     * Remove replacement
     *
     * @param \AppBundle\Entity\Conference $replacement
     */
    public function removeReplacement(\AppBundle\Entity\Conference $replacement)
    {
        $this->replacements->removeElement($replacement);
    }

    /**
     * Get replacements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReplacements()
    {
        return $this->replacements;
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
}
