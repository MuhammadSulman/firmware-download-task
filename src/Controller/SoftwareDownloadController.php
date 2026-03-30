<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SoftwareDownloadController extends AbstractController
{
    #[Route('/carplay/software-download', name: 'software_download')]
    public function index(): Response
    {
        return $this->render('software/download.html.twig');
    }

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->redirectToRoute('software_download');
    }
}
