<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

/**
 * User controller.
 *
 * @Route("user")
 */
class UserController extends AbstractController
{
    /**
     * Lists all user entities.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/", name="user_index")
     */
    public function indexAction(Request $request, PaginatorInterface $paginator)
    {
        $manager = $this->getDoctrine()->getManager();
        $query = $manager->getRepository(User::class)
            ->createQueryBuilder("u")
            ->where("u.enabled = 1")
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 10),
            10
        );

        // parameters to template
        return $this->render('user/index.html.twig', array('pagination' => $pagination));
    }


    /**
     * Finds and displays a user entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/show/{id}", name="user_show")
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/promote/{id}", name="user_promote")
     */
    public function promoteAction(Request $request, User $user)
    {
        $form = $this->createPromoteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $user->addRole("ROLE_ADMIN");
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("user_index")]);
        }

        return $this->render("user/promote.html.twig", array("promote_form"=>$form->createView()));
    }

    /**
     * Deletes a user entity.
     * @IsGranted("ROLE_ADMIN")
     * @Route("/delete/{id}", name="user_delete")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            return new JsonResponse([
                "success" => true,
                "redirect" => $this->generateUrl("user_index")]);
        }

        return $this->render("user/delete.html.twig", array("delete_form"=>$form->createView()));
    }
    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createPromoteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_promote', array('id' => $user->getId())))
            ->getForm()
            ;
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
