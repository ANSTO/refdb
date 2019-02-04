<?php
namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Author;
use AppBundle\Entity\Reference;
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
            $entityManager = $args->getObjectManager();
            $src = $entity->getAuthor();

            if (strpos($src," et al.") !== false) {
                $src = str_replace( " et al.","", $src);
                $entity->setEtAl(true);
            } else {
                $entity->setEtAl(false);
            }

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
            foreach ($authors as $author) {


                $author = trim($author);
                if (substr($author, 0, 4) == "and ") {
                    $author = substr($author, 0, 4);
                }

                $outputAuths[] = $author;

                if (preg_match_all("/^(([A-Z" . $accChars . "]{1}\.[ ]?){1,2}) ([A-Z" . $accChars . "]{1}[a-z" . $accChars . "\-]+)*$/",$author, $matches) == true) {
                    $count++;
                    $author = str_replace(" ", "", $matches[1][0]) . " " . $matches[3][0];
                    if ($firstAuthor == "") {
                        $firstAuthor = $author;
                    }

                    $auths = $repo->findBy(['name'=>$author]);


                    if (count($auths) == 0) {
                        $auth = new Author();
                        $auth->setName($author);
                        $manager->persist($auth);
                    } else {
                        $auth = $auths[0];
                        if (count($auths) > 1) {
                            /** @var Author[] $other_auths */
                            $other_auths = array_slice($auths, 1);
                            foreach ($other_auths as $other_auth) {
                                foreach ($other_auth->getReferences() as $other_auth_ref) {
                                    $auth->addReference($other_auth_ref);

                                }
                                $manager->remove($other_auth);
                            }
                        }
                    }

                    if ($auth->getReferences()->contains($entity) == false) {
                        $auth->addReference($entity);
                    }

                }
            }

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
        }
    }
}