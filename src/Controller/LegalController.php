<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/terms', name: 'app_terms')]
    public function terms(): Response
    {
        return $this->render('legal/conditions.html.twig');
    }
    
    #[Route('/privacy', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }
    
    #[Route('/cookies', name: 'app_cookies')]
    public function cookies(): Response
    {
        return $this->render('legal/cookies.html.twig');
    }
    
    #[Route('/legal-notice', name: 'app_legal_notice')]
    public function legalNotice(): Response
    {
        return $this->render('legal/mentions.html.twig');
    }
}