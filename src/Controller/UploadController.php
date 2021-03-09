<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Service\ImportService;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("upload")
 */
class UploadController extends AbstractController
{
    /**
     * Used for re-uploading conference CSVs
     * @IsGranted("ROLE_ADMIN")
     * @Route("/{id}", name="upload_index")
     */
    public function indexAction(Request $request, ImportService $importService, Conference $conference) {

        $form = $this->createFormBuilder()
            ->add("file",FileType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $imported = $importService->merge($data['file']->getPathname(), $conference);
                $this->addFlash("notice", $imported . " references imported");
            } catch (\Exception $exception) {
                $this->addFlash("notice", "Failed to import references: " . $exception->getMessage());
            }



            return $this->redirectToRoute("conference_show", ["id"=> $conference->getId()]);
        }

        return $this->render("upload/index.html.twig", ["form"=>$form->createView(),"conference"=>$conference]);
    }


}
