<?php
namespace App\EventSubscriber;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class ConferenceSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate,
        ];
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }


    public function index(LifecycleEventArgs $args)
    {
        // re-generate cache for all sub items
        $entity = $args->getObject();
        if ($entity instanceof Conference) {
            /** @var Reference $reference */
            foreach ($entity->getReferences() as $reference) {
                $reference->setCache($reference->__toString());
            }
        }
    }
}
