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
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
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

            preg_match_all("/(((([A-Z]{1}\.[ ]?){1,2}) ([A-Z]{1}[a-z]+))( [\(\[][^\)\]]+\)\]?)?)/",$src, $matches);

            $authors = $matches[2];
            $manager = $args->getObjectManager();
            $repo = $manager->getRepository(Author::class);
            $count = 0;
            $firstAuthor = "";
            foreach ($authors as $author) {
                $author = trim($author);
                if (substr($author, 0, 4) == "and ") {
                    $author = substr($author, 0, 4);
                }

                if (preg_match_all("/^(([A-Z]{1}\.[ ]?){1,2}) ([A-Z]{1}[a-z]+)$/",$author, $matches) == true) {


                    $count++;
                    $author = str_replace(" ", "", $matches[1][0]) . " " . $matches[3][0];
                    if ($firstAuthor == "") {
                        $firstAuthor = $author;
                    }
                    $auth = $repo->findOneBy(['name'=>$author]);

                    if ($auth === null) {
                        $auth = new Author();
                        $auth->setName($author);
                        $manager->persist($auth);
                    }

                    if ($auth->getReferences()->contains($entity) == false) {
                        $auth->addReference($entity);
                    }

                }

            }

            if ($count > 6) {
                $entity->setAuthor($firstAuthor . " et al.");
                $entity->setEtAl(true);
            }
            $manager->flush();
        }
    }
}