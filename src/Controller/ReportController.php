<?php

namespace App\Controller;

use App\Entity\Report;
use App\Entity\User;
use App\Entity\Commentaire;
use App\Entity\Photo;
use App\Form\ReportType;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use App\Repository\CommentaireRepository;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/report')]
class ReportController extends AbstractController
{
    #[Route('/new', name: 'app_report_new', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CommentaireRepository $commentaireRepository,
        PhotoRepository $photoRepository
    ): Response
    {
        // Création d'un nouveau report
        $report = new Report();
        
        // Récupérer les paramètres de la requête
        $reportType = $request->query->get('type');
        $itemId = $request->query->get('id');
        
        // Préremplir le formulaire avec les données de la requête
        $report->setReportType($reportType);
        $report->setReportedItemId($itemId);
        
        // Récupérer les informations de l'élément signalé
        $reportedItemInfo = null;
        
        switch ($reportType) {
            case Report::TYPE_USER:
                $user = $userRepository->find($itemId);
                if ($user) {
                    $reportedItemInfo = [
                        'type' => 'Utilisateur',
                        'name' => $user->getPseudo(),
                        'entity' => $user
                    ];
                }
                break;
                
            case Report::TYPE_COMMENT:
                $comment = $commentaireRepository->find($itemId);
                if ($comment) {
                    $reportedItemInfo = [
                        'type' => 'Commentaire',
                        'name' => 'de ' . $comment->getUser()->getPseudo(),
                        'preview' => substr($comment->getMessage(), 0, 50) . (strlen($comment->getMessage()) > 50 ? '...' : ''),
                        'entity' => $comment
                    ];
                }
                break;
                
            case Report::TYPE_POST:
                $photo = $photoRepository->find($itemId);
                if ($photo) {
                    $reportedItemInfo = [
                        'type' => 'Publication',
                        'name' => 'de ' . $photo->getUser()->getPseudo(),
                        'entity' => $photo
                    ];
                }
                break;
        }
        
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $report->setReporter($this->getUser());
                $report->setStatus(Report::STATUS_PENDING);
                
                $entityManager->persist($report);
                $entityManager->flush();
                
                $this->addFlash('success', 'Signalement envoyé avec succès. Un modérateur examinera votre demande.');
                
                // Redirection vers la page d'où provient le signalement
                $referer = $request->headers->get('referer');
                return $this->redirect($referer ?: $this->generateUrl('app_home'));
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du signalement: ' . $e->getMessage());
            }
        }
        
        return $this->render('report/new.html.twig', [
            'form' => $form->createView(),
            'reportType' => $reportType,
            'itemId' => $itemId,
            'reportedItemInfo' => $reportedItemInfo,
        ]);
    }
}