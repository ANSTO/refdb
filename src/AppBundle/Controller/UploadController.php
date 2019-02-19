<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
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
    public function indexAction(Request $request) {

        $manager = $this->getDoctrine()->getManager();
        $form = $this->createFormBuilder()
            ->add("file",FileType::class)
            ->add("conference", EntityType::class, array("class"=>Conference::class, "query_builder"=>function(EntityRepository $er) {
                return $er->createQueryBuilder('c')->orderBy('c.code', 'ASC');
            }))
            ->getForm();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            ini_set('memory_limit','1G');
            ini_set('max_execution_time', 300);

            $imported = 0;
            $format = 0;
            $duplicate = 0;

            $data = $form->getData();

            /** @var UploadedFile $uploaded */
            $uploaded = $data["file"];

            $references = $this->getRows($uploaded->getPathname());

            $conference = $data["conference"];

            /** @var Reference $reference */
            foreach ($references as $reference) {


                $valid = true;
                if (preg_match("/^\d+\-\d+$/", $reference->getPosition()) != true ||
                    preg_match("/^[A-Za-z0-9]+$/", $reference->getPaperId()) != true ||
                    $reference->getPaperId() == "" || $reference->getAuthor() == "" || $reference->getTitle() == "" || $reference->getPosition() == "") {
                    $valid = false;
                    $format++;
                    $errors[] = $reference->getPaperId();
                }

                $exists = $manager->getRepository(Reference::class)->findBy([
                    "conference"=>$conference,
                    "paperId"=>$reference->getPaperId()]);

                if (count($exists) > 0) {
                    $valid = false;
                    $duplicate++;
                }

                if ($valid) {
                    $reference->setConference($conference);
                    $manager->persist($reference);
                    $imported++;
                }
            }

            $this->addFlash("notice", $imported . " entries imported");
            $this->addFlash("notice", $format . " entries ignored due to incorrect format " . implode(", ", $errors));
            $this->addFlash("notice", $duplicate . " entries are duplicates");

            $manager->flush();

            return $this->redirectToRoute("author_clean");

        }

        return $this->render("upload/index.html.twig", ["form"=>$form->createView()]);
    }


}
