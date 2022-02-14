<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Reference;
use App\Form\BasicSearchType;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Author controller.
 *
 * @Route("author")
 */
class AuthorController extends AbstractController
{
    /**
     * Lists all author entities.
     * @Route("/", name="author_index")
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BasicSearchType::class, null, ["method"=>"GET"]);
        $form->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();
        $search = $manager->getRepository(Author::class)
            ->createQueryBuilder("a");
        $search->addSelect('SIZE(a.references) as HIDDEN total');
        if ($form->isSubmitted() && $form->isValid()) {
            
            $terms = $form->get('terms')->getData();
            
            $parts = explode(".", $terms);

            // get initials
            if (count($parts) > 1) {
                $lastName = trim(end($parts), ". ");
                $firstInitial = trim($parts[0], ". ");

                $search->orWhere("LOWER(a.name) LIKE :first")
                    ->setParameter("first", mb_strtolower($firstInitial) . '. ' . mb_strtolower($lastName) . '%');

                if (count($parts) > 2) {
                    $middleInitials = array_slice($parts, 1, -1);

                    $middleInitials = array_map(function($initial) { return trim($initial); }, $middleInitials);

                    $joined = implode("", $middleInitials);
                    $dotted = implode(". ", $middleInitials);

                    $search->orWhere("LOWER(a.name) LIKE :joined")
                        ->setParameter("joined", mb_strtolower($firstInitial) . '. ' . mb_strtolower($joined) . '. ' . mb_strtolower($lastName) . '%');

                    $search->orWhere("LOWER(a.name) LIKE :dotted")
                        ->setParameter("dotted", mb_strtolower($firstInitial) . '. ' . mb_strtolower($dotted) . '. ' . mb_strtolower($lastName) . '%');
                } else {
                    $search->orWhere("LOWER(a.name) LIKE :more")
                        ->setParameter("more", mb_strtolower($firstInitial) . '. %. ' . mb_strtolower($lastName) . '%');
                }

            } else {
                $lastName = end($parts);
                $search->orWhere("LOWER(a.name) LIKE :terms")
                    ->setParameter("terms", '%' . mb_strtolower($lastName) . '%');
            }
            

        }

        $pagination = $paginator->paginate(
            $search->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('author/index.html.twig', array(
            'pagination' => $pagination,
            'search' => $form->createView()
        ));
    }

    /**
     * Creates a new author entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/new", name="author_new")
     */
    public function newAction(Request $request)
    {
        $author = new Author();
        $form = $this->createForm('App\Form\AuthorType', $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($author);
            $em->flush();

            return $this->redirectToRoute('author_show', array('id' => $author->getId()));
        }

        return $this->render('author/new.html.twig', array(
            'author' => $author,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a author entity.
     *
     * @Route("/show/{id}", name="author_show")
     */
    public function showAction(Request $request, Author $author, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BasicSearchType::class, null, ["method"=>"GET"]);
        $form->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();
        $search = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->innerJoin("r.authors","a")
            ->where(":author = a")
            ->setParameter("author", $author);

        if ($form->isSubmitted() && $form->isValid()) {
            $terms = mb_strtolower($form->get('terms')->getData());
            $search
                ->andWhere('LOWER(r.cache) LIKE :terms')
                ->setParameter("terms", '%' . $terms . "%");
        }

        $pagination = $paginator->paginate(
            $search->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('author/show.html.twig', array(
            'author' => $author,
            'pagination' => $pagination,
            "search"=>$form->createView()));

    }

    /**
     * Displays a form to edit an existing author entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="author_edit")
     */
    public function editAction(Request $request, Author $author)
    {
        $deleteForm = $this->createDeleteForm($author);
        $editForm = $this->createForm('App\Form\AuthorType', $author);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('author_edit', array('id' => $author->getId()));
        }

        return $this->render('author/edit.html.twig', array(
            'author' => $author,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a author entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="author_delete")
     * @param Request $request
     * @param Author $author
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, Author $author)
    {
        $form = $this->createDeleteForm($author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($author);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("author_index")]);
        }

        return $this->render("author/delete.html.twig", array("delete_form"=>$form->createView()));
    }

    /**
     * Creates a form to delete a author entity.
     *
     * @param Author $author The author entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Author $author)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('author_delete', array('id' => $author->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/search/{query}", name="author_search", options={"expose"=true})
     */
    public function searchAction($query) {
        $results = $this->getDoctrine()->getManager()->getRepository(Author::class)
            ->search($query);
        return new JsonResponse($results);
    }
}
