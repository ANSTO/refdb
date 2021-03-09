<?php

namespace App\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TagTransformer implements DataTransformerInterface {

    private $manager;
    private $entity;

    public function __construct(EntityManagerInterface $manager, $entity) {
        $this->manager = $manager;
        $this->entity = $entity;
    }

    /**
     * Proposals to ID string
     *
     * @param ArrayCollection|null $entities
     * @return mixed|string
     */
    public function transform($entities) {
        if(null === $entities) {
            return '';
        }

        $response = [];
        foreach ($entities as $entity) {
            $response[] = $entity;
        }

        return json_encode($response);
    }

    /**
     * String to array of proposals
     *
     * @param string $value
     * @return ArrayCollection
     * @throws TransformationFailedException
     * @throws Exception
     */
    public function reverseTransform($value) {
        if(!$value) {
            return new ArrayCollection();
        }

        $repository = $this->manager->getRepository($this->entity);

        if($this->isJSON($value)) {
            $data = json_decode($value, true);
            $ids = array();
            foreach($data as $datum) {
                $ids[] = $datum['id'];
            }
        } else {
            throw new Exception("Must be a json response");
        }

        $entities = new ArrayCollection();
        foreach($ids as $id) {
            $entity = $repository->find($id);
            if($entity !== null) {
                $entities->add($entity);
            } else {
                throw new TransformationFailedException(sprintf('A ' . $this->entity . ' with the ID %s does not exist', $entity));
            }
        }

        return $entities;
    }

    private function isJSON($string) {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
