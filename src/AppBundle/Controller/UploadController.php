<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
use AppBundle\Service\ImportService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("upload")
 */
class UploadController extends Controller
{
    private function getRows($filename) {
        $references = [];
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 8000, ",")) !== FALSE) {
                $reference = new Reference();
                $reference->setAuthor(trim($data[1]));
                $reference->setTitle(trim($data[2]));
                $reference->setPaperId(trim(strtoupper($data[0])));
                $reference->setInProc(true);
                $reference->setPosition(trim(str_replace(" ", "-",$data[3])));
                $references[] = $reference;
            }
        }
        return $references;
    }

    /**
     * @Route("/", name="upload_index")
     */
    public function indexAction(Request $request, ImportService $importService) {

        $manager = $this->getDoctrine()->getManager();
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
            $imported = $importService->import($data['file']->getPathname(), $conference);

            $this->addFlash("notice", $imported . " references imported");

            return $this->redirectToRoute("conference_show", ["id"=> $conference->getId()]);
        }

        return $this->render("upload/index.html.twig", ["form"=>$form->createView()]);
    }


}
