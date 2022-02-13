<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use App\Form\BasicSearchType;
use App\Form\Type\TagsAsInputType;
use App\Service\DoiService;
use App\Service\FormService;
use App\Service\PaperService;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reference controller.
 *
 * @Route("reference")
 */
class ReferenceController extends AbstractController
{
    private $safeRef = "/^((?!\/\/)[a-zA-Z0-9\/._])+$/";

    /**
     * Clear the 'current conference' option
     *
     * @Route("/format", name="conference_format")
     */
    public function formAction(Request $request, FormService $formService) {
        if (preg_match($this->safeRef, $request->get('ref'))) {
            $formService->toggleForm();
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("homepage");
    }


    /**
     * Lists all reference entities.
     * @Route("/", name="reference_index")
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BasicSearchType::class, null, ["method"=>"GET"]);
        $form->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();
        $search = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r");

        if ($form->isSubmitted() && $form->isValid()) {
            $terms = mb_strtolower($form->get('terms')->getData());
            $terms = str_replace("’","'",$terms);

            $search
                ->where('LOWER(r.cache) LIKE :terms')
                ->setParameter("terms", '%' . $terms . "%");
        }

        $pagination = $paginator->paginate(
            $search->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('reference/index.html.twig', array(
            'pagination' => $pagination,
            "search"=>$form->createView()));
    }

    /**
     * Creates a new reference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/new/{id}", name="reference_new", defaults={"id": null})
     * @param Request $request
     * @param Conference|null $conference
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request, Conference $conference = null)
    {
        $reference = new Reference();
        $reference->setConference($conference);
        $form = $this->createForm('App\Form\ReferenceType', $reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($reference);
            $em->flush();

            return $this->redirectToRoute('reference_show', array('id' => $reference->getId()));
        }

        return $this->render('reference/new.html.twig', array(
            'reference' => $reference,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a reference entity.
     *
     * @Route("/show/{id}", name="reference_show", options={"expose"=true})
     * @param Reference $reference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Reference $reference)
    {
        $warning = "";
        if (($reference->getInProc() && $reference->getConference()->isUseDoi() && !$reference->isDoiVerified()) ||
            ($reference->getCustomDoi() !== null && $reference->getCustomDoi() !== "" && !$reference->isDoiVerified())) {
            $doiService = new DoiService();
            
            $valid = $doiService->check($reference);
            if (!$valid) {
                $warning = "This references DOI could not be verified, so it has been removed.";
            } else {
                $reference->setDoiVerified(true);
            }
        }

        $paperService = new PaperService();
        $update = $paperService->check($reference);


        if ($update || $reference->__toString() !== $reference->getCache()) {
            $reference->setCache($reference->__toString());
            $this->getDoctrine()->getManager()->flush();
        }

        if ($reference->hasTitleIssue()) {
            $warning .= "This papers title is all uppercase, you must correct this before using this reference.\n\n";
        }

        if (preg_match_all("/[\[\(\/]+/",$reference->getAuthor(), $matches) || count($reference->getAuthors()) == 0) {
            $warning .= "There is a problem with this papers authors.\n\n";
        }
        if (($reference->getConference()->isPublished() && $reference->getInProc() && $reference->getPosition() != "na" && ($reference->getPosition() === null || $reference->getPosition() == "" || $reference->getPosition() == "99-98"))) {
            $warning .= "The page numbers could not be added automatically for this paper. ";
            $warning .= "You must provide the page numbers from the original proceedings which is located at JACoW.org, and substitute ‘pp. XX-XX’ with the correct page numbers.\n";
            $warning .= "* Please report these numbers by clicking on the ‘Fix a problem’ button as an Admin will be able to update this reference for future results.    \n\n";
        }



        $deleteForm = $this->createDeleteForm($reference);

        return $this->render('reference/show.html.twig', array(
            'reference' => $reference,
            'warning' => $warning,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing reference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="reference_edit")
     */
    public function editAction(Request $request, Reference $reference)
    {
        $manager = $this->getDoctrine()->getManager();
        $deleteForm = $this->createDeleteForm($reference);
        $originalAuthors = clone $reference->getAuthors();

        $editForm = $this->createForm('App\Form\ReferenceType', $reference)
            ->add('authors', TagsAsInputType::class, [
                "entity_class"=> Author::class,
                "data_source" => "author_search",
                "label"=> "Associated Authors (un-ordered)"]);

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $newAuthors = $reference->getAuthors();

            /** @var Author $author */
            foreach ($originalAuthors as $author) {
                if ($newAuthors->contains($author) == false) {
                    $linkedAuthor = $manager->getRepository(Author::class)->find($author->getId());
                    $linkedAuthor->removeReference($reference);
                }
            }

            /** @var Author $author */
            foreach ($newAuthors as $author) {
                if ($originalAuthors->contains($author) == false) {
                    $author->addReference($reference);
                    $manager->persist($author);
                }
            }

            $manager->flush();

            return $this->redirectToRoute('reference_show', array('id' => $reference->getId()));
        }

        return $this->render('reference/edit.html.twig', array(
            'reference' => $reference,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a reference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="reference_delete")
     */
    public function deleteAction(Request $request, Reference $reference)
    {
        $form = $this->createDeleteForm($reference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($reference);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("reference_index")]);
        }

        return $this->render("reference/delete.html.twig", array("delete_form"=>$form->createView()));
    }


    /**
     * Creates a form to delete a reference entity.
     *
     * @param Reference $reference The reference entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Reference $reference)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('reference_delete', array('id' => $reference->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
