<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Conference;
use AppBundle\Entity\Reference;
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
    /**
     * @Route("/", name="upload_index")
     */
    public function indexAction(Request $request) {

        $manager = $this->getDoctrine()->getManager();
        $form = $this->createFormBuilder()
            ->add("file",FileType::class)
            ->add("conference", EntityType::class, array("class"=>Conference::class))
            ->getForm();
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $imported = 0;
            $format = 0;
            $duplicate = 0;

            $data = $form->getData();

            /** @var UploadedFile $uploaded */
            $uploaded = $data["file"];

            $contents = file($uploaded->getPathname());

            $lines = count($contents);

            $papers = floor($lines / 4);

            $conference = $data['conference'];

            $errors = [];
            for ($p = 0; $p < $papers; $p++) {
                $l = $p * 4;
                $reference = new Reference();



                $paperId = trim(strtoupper($contents[$l]));
                $authors = trim($contents[$l+1]);
                $title = trim($contents[$l+2]);
                $position = trim(str_replace(" ", "-",$contents[$l+3]));

                // check valid input

                $valid = true;
                if (preg_match("/^\d+\-\d+$/", $position) != true ||
                    preg_match("/^[A-Za-z0-9]+$/", $paperId) != true ||
                    $paperId == "" || $authors == "" || $title == "" || $position == "") {
                    $valid = false;
                    $format++;
                    $errors[] = $paperId;
                }

                $exists = $manager->getRepository(Reference::class)->findBy([
                    "conference"=>$conference,
                    "paperId"=>$paperId]);

                if (count($exists) > 0) {
                    $valid = false;
                    $duplicate++;
                }

                if ($valid) {
                    $reference->setPaperId($paperId);
                    $reference->setAuthor($authors);
                    $reference->setTitle($title);
                    $reference->setPosition($position);
                    $reference->setConference($conference);
                    $reference->setInProc(true);
                    $manager->persist($reference);
                    $imported++;
                }
            }

            $this->addFlash("notice", $imported . " entries imported");
            $this->addFlash("notice", $format . " entries ignored due to incorrect format " . implode(", ", $errors));
            $this->addFlash("notice", $duplicate . " entries are duplicates");

            $manager->flush();

        }

        return $this->render("upload/index.html.twig", ["form"=>$form->createView()]);
    }
}
