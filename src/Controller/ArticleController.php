<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return new Response('<html><body><h1>Bienvenue sur le projet Symfony !</h1></body></html>');
    }
}
