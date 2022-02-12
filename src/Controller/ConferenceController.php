<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Conference;
use App\Entity\Reference;
use App\Form\BasicSearchType;
use App\Http\CsvResponse;
use App\Service\CurrentConferenceService;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Conference controller.
 *
 * @Route("conference")
 */
class ConferenceController extends AbstractController
{
    private $safeRef = "/^((?!\/\/)[a-zA-Z0-9\/._])+$/";

    /**
     * Dismiss the notification for current conference.
     *
     * @Route("/dismiss", name="conference_dismiss")
     * @param Request $request
     * @param CurrentConferenceService $currentConferenceService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function dismissAction(Request $request, CurrentConferenceService $currentConferenceService) {
        // Confirm referral URL is internal only (only contains alpha num and slash)
        if (preg_match($this->safeRef, $request->get('ref'))) {
            $currentConferenceService->dismiss();
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("homepage");
    }

    /**
     * Clear the 'current conference' option
     *
     * @Route("/clear/current", name="conference_current_clear")
     */
    public function clearAction(Request $request, CurrentConferenceService $currentConferenceService) {
        if (preg_match($this->safeRef, $request->get('ref'))) {
            $currentConferenceService->clearCurrent();
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("homepage");
    }

    /**
     * Make this conference my current conference (changes the way the reference appears)
     *
     * @Route("/current/{id}", name="conference_current")
     */
    public function currentAction(Request $request, CurrentConferenceService $currentConferenceService, Conference $conference) {
        if (preg_match($this->safeRef, $request->get('ref'))) {
            $currentConferenceService->setCurrent($conference);
            return $this->redirect($request->get('ref'));
        }
        return $this->redirectToRoute("homepage");
    }

    /**
     * Lists all conference entities.
     * @Route("/", name="conference_index")
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BasicSearchType::class, null, ["method"=>"GET"]);
        $form->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();
        $search = $manager->getRepository(Conference::class)
            ->createQueryBuilder("c");

        if ($form->isSubmitted() && $form->isValid()) {
            $terms = mb_strtolower($form->get('terms')->getData());

            $terms = str_replace("’","'",$terms);

            $search->orWhere('LOWER(c.code) LIKE :terms')
                ->orWhere('LOWER(c.name) LIKE :terms')
                ->orWhere('LOWER(c.year) LIKE :terms')
                ->setParameter("terms", $terms . "%")
                ->orWhere('LOWER(c.location) LIKE :location')
                ->setParameter("location", '%' . $terms . "%");
            //
            $abbreviated = str_replace("international", "int.", $terms);
            $abbreviated = str_replace("conference", "conf.", $abbreviated);

            $search
                ->orWhere('LOWER(c.name) LIKE :abbreviated')
                ->setParameter("abbreviated", $abbreviated . "%");

            if (preg_match("/(\d{4})/", $terms, $matches)) {
                foreach ($matches as $match) {
                    $terms = str_replace($match, substr($match,2), $terms);
                }
                $search->orWhere('LOWER(c.code) LIKE :date')
                    ->orWhere('LOWER(c.year) LIKE :date')
                    ->setParameter('date','%' . $terms);
            }
        }

        $pagination = $paginator->paginate(
            $search->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        // parameters to template
        return $this->render('conference/index.html.twig', array('pagination' => $pagination, 'search'=> $form->createView()));
    }

    /**
     * Make this conference my current conference (changes the way the reference appears)
     * @IsGranted("ROLE_ADMIN")
     * @Route("/export/{id}", name="conference_export")
     * @param Request $request
     * @param Conference $conference
     * @return CsvResponse
     */
    public function export(Request $request, Conference $conference) {
        $references = $this->getDoctrine()->getRepository(Reference::class)->createQueryBuilder("r")
            ->leftJoin("r.authors", "a")
            ->where("r.conference = :conference")
            ->setParameter("conference",$conference)
            ->getQuery()
            ->getResult();

        $output = [];

        /** @var Reference $reference */
        foreach ($references as $reference) {
            $item = [];
            $item["paper"] = $reference->getPaperId();
            $authors = [];
            /** @var Author $author */
            foreach ($reference->getAuthors() as $author) {
                $authors[] = $author->getName();
            }
            if (count($authors) == 0) {
                $item["authors"] = $reference->getOriginalAuthors();
            } else {
                $item["authors"] = implode(", ", $authors);
            }

            $item["title"] = $reference->getTitle();
            $item["position"] = $reference->getPosition();
            $item["contribution"] = $reference->getContributionId();
            $item["doi"] = $reference->getCustomDoi();
            $item["url"] = $reference->getPaperUrl();

            $output[] = $item;
        }

        return new CsvResponse($output);

    }

    /**
     * Creates a new conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/new", name="conference_new")
     */
    public function newAction(Request $request)
    {
        $conference = new Conference();
        $form = $this->createForm('App\Form\ConferenceType', $conference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($conference);
            $em->flush();

            return $this->redirectToRoute('conference_show', array('id' => $conference->getId()));
        }

        return $this->render('conference/new.html.twig', array(
            'conference' => $conference,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a conference entity.
     *
     * @Route("/show/{id}", name="conference_show")
     * @param Request $request
     * @param Conference $conference
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request, Conference $conference, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BasicSearchType::class, null, ["method"=>"GET"]);
        $form->handleRequest($request);

        $manager = $this->getDoctrine()->getManager();
        $search = $manager->getRepository(Reference::class)
            ->createQueryBuilder("r")
            ->where("r.conference = :conference")
            ->setParameter("conference", $conference);

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

        return $this->render('conference/show.html.twig', array(
            'conference' => $conference,
            'pagination' => $pagination,
            "search"=>$form->createView()));

    }

    /**
     * Displays a form to edit an existing conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/edit/{id}", name="conference_edit")
     */
    public function editAction(Request $request, Conference $conference)
    {
        $deleteForm = $this->createDeleteForm($conference);
        $editForm = $this->createForm('App\Form\ConferenceType', $conference);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash("success", "Conference settings saved!");
            return $this->redirectToRoute('conference_edit', array('id' => $conference->getId()));
        }

        return $this->render('conference/edit.html.twig', array(
            'conference' => $conference,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a conference entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="conference_delete")
     */
    public function deleteAction(Request $request, Conference $conference)
    {
        $form = $this->createDeleteForm($conference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($conference);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("conference_index")]);
        }

        return $this->render("conference/delete.html.twig", array("delete_form"=>$form->createView()));
    }

    /**
     * Creates a form to delete a conference entity.
     *
     * @param Conference $conference The conference entity
     *
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(Conference $conference)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('conference_delete', array('id' => $conference->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/search/{query}/{type}", name="conference_search", options={"expose"=true})
     * @param $query
     * @param string $type
     * @return JsonResponse
     */
    public function searchAction($query, $type = "name") {
        $results = $this->getDoctrine()->getManager()->getRepository(Conference::class)
            ->search($query, $type);
        return new JsonResponse($results);
    }
}
