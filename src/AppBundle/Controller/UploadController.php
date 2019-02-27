<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Conference;
use AppBundle\Service\ImportService;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("upload")
 */
class UploadController extends Controller
{
    /**
     * Used for re-uploading conference CSVs
     * @IsGranted("ROLE_ADMIN")
     * @Route("/", name="upload_index")
     */
    public function indexAction(Request $request, ImportService $importService) {

        $form = $this->createFormBuilder()
            ->add("file",FileType::class)
            ->add("conference", EntityType::class, array("class"=>Conference::class, "query_builder"=>function(EntityRepository $er) {
                return $er->createQueryBuilder('c')->orderBy('c.code', 'ASC');
            }))
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var Conference $conference */
            $conference = $data["conference"];
            try {
                $imported = $importService->import($data['file']->getPathname(), $conference);
                $this->addFlash("notice", $imported . " references imported");
            } catch (\Exception $exception) {
                $this->addFlash("notice", "Failed to import references: " . $exception->getMessage());
            }

            return $this->redirectToRoute("conference_show", ["id"=> $conference->getId()]);
        }

        return $this->render("upload/index.html.twig", ["form"=>$form->createView()]);
    }


}
