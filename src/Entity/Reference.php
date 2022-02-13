<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Reference
 *
 * @ORM\Table(name="reference",indexes={
 *     @ORM\Index(name="reference_paper_idx", columns={"cache"}) })
 * @ORM\Entity(repositoryClass="App\Repository\ReferenceRepository")
 */
class Reference implements \JsonSerializable
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
     * The original imported author string to help aid with correcting errors.
     * @var string
     * @ORM\Column(type="string", length=4000, nullable=true)
     */
    private $originalAuthors;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    private $contributionId;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paperId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=2000, nullable=true)
     */
    private $title;

    /**
     * Author string component
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=4000, nullable=true)
     */
    private $author;

    /**
     * Associated authors
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Author", mappedBy="references")
     */
    private $authors;

    /**
     * @var Conference
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Conference", inversedBy="references")
     */
    private $conference;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=255, nullable=true)
     */
    private $position;

    /**
     * Unused so far.
     *
     * @var bool
     *
     * @ORM\Column(name="in_proc", type="boolean", nullable=true)
     */
    private $inProc;

    /**
     * Indicates whether or not Et al. is being used in the author string
     * @var bool
     *
     * @ORM\Column(name="et_al", type="boolean", nullable=true)
     */
    private $etAl;

    /**
     * Cached reference for string representation purposes.
     * @var string
     *
     * @ORM\Column(name="cache", type="string", length=4000, nullable=true)
     */
    private $cache;

    /**
     * Whether or not the doi has been confirmed to exist over the web.
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $doiVerified;

    /**
     * URL To paper
     * @var string
     *
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $customDoi;

    /**
     * URL To paper
     * @var string
     *
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $paperUrl;

    /**
     * Any associated issues will be logged here.
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Feedback", mappedBy="reference", cascade={"remove"})
     * @var ArrayCollection
     */
    private $feedback;


    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\Favourite", mappedBy="reference", cascade={"remove"})
     */
    private $favourites;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->feedback = new ArrayCollection();
        $this->favourites = new ArrayCollection();
    }

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
     * Set title
     *
     * @param string $title
     *
     * @return Reference
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
     * @param string $author
     *
     * @return Reference
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
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
     * @return Reference
     */
    public function setConference($conference)
    {
        $this->conference = $conference;

        return $this;
    }

    /**
     * Get conference
     *
     * @return Conference
     */
    public function getConference()
    {
        return $this->conference;
    }

    /**
     * Set position
     *
     * @param string $position
     *
     * @return Reference
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }


    /**
     * @return bool
     */
    public function isInProc()
    {
        return $this->inProc;
    }

    /**
     * @param bool $inProc
     */
    public function setInProc($inProc)
    {
        $this->inProc = $inProc;
    }



    /**
     * @return ArrayCollection
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * @param ArrayCollection $authors
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;
    }



    /**
     * @return string
     */
    public function getAuthorStr() {
        $author = $this->getAuthor();
        if ($author === null) {
            $authors = $this->getAuthors();
            if (count($authors) >= 6) {
                return $authors[0] . "<em>et al.</em>";
            } elseif (count($authors) > 0) {
                return implode(", ", $authors);
            }
        }
        return $author;
    }

    public function __toString()
    {
        return $this->format();

    }

    public function format($format = "long") {
        $output = $this->getTitleSection() . "" . $this->getConferenceSection($format);

        if (trim($this->getPaperSection()) !== "") {
            $output .= "," . $this->getPaperSection();
        }

        if ($this->getInProc() == false || $this->getConference()->isPublished() == false) {
            $output .= ", unpublished";
        }

        $output .= ".";

        return $output;
    }

    public function getConferenceSection($format) {
        if ($format == "long") {
            $section = $this->conference;
        } else {
            $section = $this->conference->getCode();
        }

        $section .= "</em>, " . $this->conference->getLocation() . ", " . $this->conference->getYear();
        return $section;
    }

    public function hasTitleIssue() {
        // detect all upper case
        return (preg_match("/^[0-9A-Z ]+$/",$this->getTitle()));
    }

    public function getTitleCaseCorrected() {
        $title = $this->getTitle();

       // if ($this->hasTitleIssue()) {
       //     $title = ucwords(strtolower($title));
       // }

        return $title;
    }

    public function getTitleSection() {
        $author = $this->getAuthorStr();
        $title = $this->getTitleCaseCorrected();



        if ($this->isInProc() && $this->getConference()->isPublished() && $this->getInProc()) {
            $inProc = "in <em>Proc. ";
        } else {
            $inProc = "presented at the ";
        }

        return $author . ", “" . $title . "”, " . $inProc;
    }

    public function doi() {
        if ($this->getCustomDoi() !== null && $this->getCustomDoi() !== "") {
            return 'https://doi.org/' . $this->getCustomDoi();
        } elseif ($this->getInProc() && $this->getConference()->isUseDoi()) {
            return 'https://doi.org/10.18429/JACoW-' . $this->getConference()->getDoiCode() . '-' . $this->getPaperId();
        } else {
            return false;
        }
    }

    public function doiText() {
        if ($this->getCustomDoi() !== null && $this->getCustomDoi() !== "") {
            return 'doi:' . $this->getCustomDoi();
        }
        elseif ($this->getConference()->isUseDoi() && $this->getInProc()) {
            return 'doi:10.18429/JACoW-' . $this->getConference()->getDoiCode() . '-' . $this->getPaperId();
        }
        return "";
    }

    public function getFirstLastName() {
        $authorString = $this->getAuthor();
        $authors = preg_split("/and|,/", $authorString);

        $author = $authors[0];

        $parts = explode(". ",$author);
        foreach ($parts as $part) {
            $name = trim($part," ,-");
            if (strlen($name) > 2) {
                $name = str_replace(" et al.","",$name);
                return $name . ":";
            }
        }
        $author = str_replace(".","", $author);
        $author = str_replace(" ", "", $author);
        $author = str_replace(" et al.","",$author);
        return $author . ":";
    }

    public function getPaperSection() {

        $position = "";

        if ($this->getConference()->isPublished() && $this->getInProc()) {
            if ($this->getPosition() !== null && $this->getPosition() !== "99-98" && $this->getPosition() != "na") {
                $position = "pp. " . $this->getPosition();
            } else {
                if ($this->getPosition() != "na") {
                    $position = "pp. XX-XX";
                }
            }
        }
        $paper = " ";
        if ($this->getPaperId() !== null && !($this->getConference()->isUseDoi() && $this->isDoiVerified())) {
            $paper = " paper " . $this->getPaperId() . ", ";
        }

        return rtrim($paper . $position,", ");
    }



    /**
     * @return string
     */
    public function getPaperId()
    {
        return $this->paperId;
    }

    /**
     * @param string $paperId
     */
    public function setPaperId($paperId)
    {
        $this->paperId = $paperId;
    }


    /**
     * Get inProc
     *
     * @return boolean
     */
    public function getInProc()
    {
        return $this->inProc;
    }

    /**
     * Add author
     *
     * @param Author $author
     *
     * @return Reference
     */
    public function addAuthor(Author $author)
    {
        $this->authors[] = $author;

        return $this;
    }

    /**
     * Remove author
     *
     * @param Author $author
     */
    public function removeAuthor(Author $author)
    {
        $this->authors->removeElement($author);
    }

    /**
     * Set etAl
     *
     * @param boolean $etAl
     *
     * @return Reference
     */
    public function setEtAl($etAl)
    {
        $this->etAl = $etAl;

        return $this;
    }

    /**
     * Get etAl
     *
     * @return boolean
     */
    public function getEtAl()
    {
        return $this->etAl;
    }

    /**
     * @return mixed
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param mixed $cache
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getCache()
        ];
    }

    /**
     * @return bool
     */
    public function isDoiVerified()
    {
        return $this->doiVerified;
    }

    /**
     * @param bool $doiVerified
     */
    public function setDoiVerified($doiVerified)
    {
        $this->doiVerified = $doiVerified;
    }

    /**
     * @return string
     */
    public function getOriginalAuthors()
    {
        return $this->originalAuthors;
    }

    /**
     * @param string $originalAuthors
     */
    public function setOriginalAuthors($originalAuthors)
    {
        $this->originalAuthors = $originalAuthors;
    }

    /**
     * @return ArrayCollection
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * @param ArrayCollection $feedback
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;
    }

    /**
     * @return mixed
     */
    public function getFavourites()
    {
        return $this->favourites;
    }

    /**
     * @param mixed $favourites
     */
    public function setFavourites($favourites)
    {
        $this->favourites = $favourites;
    }

    /**
     * @return int
     */
    public function getContributionId()
    {
        return $this->contributionId;
    }

    /**
     * @param int $contributionId
     */
    public function setContributionId($contributionId)
    {
        $this->contributionId = $contributionId;
    }

    /**
     * @return string
     */
    public function getPaperUrl()
    {
        return $this->paperUrl;
    }

    /**
     * @param string $paperUrl
     */
    public function setPaperUrl($paperUrl)
    {
        $this->paperUrl = $paperUrl;
    }

    /**
     * @return string
     */
    public function getCustomDoi()
    {
        return $this->customDoi;
    }

    /**
     * @param string $customDoi
     */
    public function setCustomDoi($customDoi)
    {
        $this->customDoi = $customDoi;
    }
}
