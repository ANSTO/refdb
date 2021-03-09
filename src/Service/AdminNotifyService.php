<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 2019-02-19
 * Time: 18:33
 */

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Swift_Mailer;
use Swift_Message;
use Twig\Environment;

class AdminNotifyService
{
    private $manager;
    private $mailer;
    private $twig;
    private $fromAddress;
    private $rootDir;

    public function __construct(EntityManagerInterface $objectManager, Swift_Mailer $mailer, $fromAddress, Environment $twig, $rootDir)
    {
        $this->mailer = $mailer;
        $this->manager = $objectManager;
        $this->twig = $twig;
        $this->rootDir = $rootDir;
        $this->fromAddress = $fromAddress;
    }

    public function sendAll($title, $content) {
        /** @var User[] $admins */
        $admins = $this->manager->getRepository(User::class)->findByRole("ROLE_ADMIN");
        foreach ($admins as $admin) {
            $this->sendMessage($admin->getEmail(),$title, $content);
        }
    }

    public function sendMessage($to, $title, $content) {

        // Send emails
        $logoPath = $this->rootDir. "/../web/images/jacow_image.png";
        $message = new Swift_Message();
        $logoID =  $message->embed(\Swift_Image::fromPath($logoPath));
        $message
            ->setSubject("JaCoW Reference Search - Admin Notification [SEC=UNCLASSIFIED]")
            ->setFrom($this->fromAddress)
            ->setTo($to)
            ->setBody(
                $this->twig->render(
                    'email/email.html.twig', array(
                        'logoID' => $logoID,
                        'title' => $title,
                        'content' => $content
                    )
                ), 'text/html'
            );
        try {
            $this->mailer->send($message);
        } catch(Exception $exception) {

        }
    }
}
