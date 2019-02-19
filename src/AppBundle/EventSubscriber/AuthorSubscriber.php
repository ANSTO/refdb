<?php
namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Author;
use AppBundle\Entity\Reference;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class AuthorSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
        $accChars = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        $entity = $args->getObject();
        if ($entity instanceof Reference) {
            $src = $entity->getAuthor();

            // Is the author list already "Et al."
            if (strpos($src," et al.") !== false) {
                $src = str_replace( " et al.","", $src);
                $entity->setEtAl(true);
            } else {
                $entity->setEtAl(false);
            }

            // Fetch all the authors out of the text area.
            preg_match_all("/((((([A-Z" . $accChars . "]{1}\.[ ]?){1,2}) ([A-Z" . $accChars . "]{1}[a-z" . $accChars . "\-]+)*))( [\(\[][^\)\]]+\)\]?)?)/",$src, $matches);

            if (count($matches[1]) == 0) {
                return;
            }

            $authors = $matches[1];
            $manager = $args->getObjectManager();
            $repo = $manager->getRepository(Author::class);
            $count = 0;
            $firstAuthor = "";
            $outputAuths = [];

            $cleanedAuthors = [];
            // Loop through the authors and add them to the database
            foreach ($authors as $author) {
                // Clean up the authors name
                $author = trim($author);
                if (substr($author, 0, 4) == "and ") {
                    $author = substr($author, 0, 4);
                }

                if (!in_array($author, $cleanedAuthors)) {
                    $cleanedAuthors[] = $author;
                }
            }


            foreach ($cleanedAuthors as $author) {
                $outputAuths[] = $author;

                if (preg_match_all("/^(([A-Z" . $accChars . "]{1}\.[ ]?){1,2}) ([A-Z" . $accChars . "]{1}[a-z" . $accChars . "\-]+)*$/",$author, $matches) == true) {
                    $count++;
                    $author = str_replace(" ", "", $matches[1][0]) . " " . $matches[3][0];
                    if ($firstAuthor == "") {
                        $firstAuthor = $author;
                    }

                    /** @var Author[] $auths */
                    $auths = $repo->findBy(['name'=>$author]);

                    if (count($auths) == 0) {
                        // Add Author
                        $auth = new Author();
                        $auth->setName($author);
                        $auth->addReference($entity);
                        $manager->persist($auth);
                    } else {
                        // Select the first author
                        $auth = $auths[0];

                        if ($auth->getReferences()->contains($entity) == false) {
                            $auth->addReference($entity);
                        }

                        // Consolidate any other authors of the same name
                        if (count($auths) > 1) {
                            /** @var ArrayCollection $otherAuths */
                            $otherAuths = array_slice($auths,1);
                            foreach ($otherAuths as $otherAuth) {
                                foreach ($otherAuth->getReferences() as $otherAuthRef) {
                                    $auth->addReference($otherAuthRef);
                                }
                                $manager->remove($otherAuth);
                            }
                        }
                    }

                }
            }

            // Reform author text to be only the required content.
            if ($count > 6 || $entity->getEtAl()) {
                $entity->setAuthor($firstAuthor . " et al.");
                $entity->setEtAl(true);
            } else {
                if (count($outputAuths) <= 2) {
                    $entity->setAuthor(implode(" and ", $outputAuths));
                } else {
                    $entity->setAuthor(implode(', ', array_slice($outputAuths, 0, -1)) . ', and ' . end($outputAuths));
                }
            }

            $entity->setCache($entity->__toString());
        }
    }
}