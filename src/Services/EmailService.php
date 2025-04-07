<?php

namespace App\Services;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

/**
 * Service pour l'envoi d'emails
 */
class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private string $senderEmail;
    private string $senderName;

    /**
     * Constructeur du service d'email
     */
    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $senderEmail = 'noreply@footconnect.com',
        string $senderName = 'FootConnect'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Envoie un email avec un template Twig
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Objet de l'email
     * @param string $template Chemin du template Twig
     * @param array $context Variables à passer au template
     * @throws TransportExceptionInterface
     */
    public function sendEmail(string $to, string $subject, string $template, array $context = []): void
    {
        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($to)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context($context);

            $this->mailer->send($email);
            $this->logger->info('Email envoyé à {to} avec le sujet : {subject}', [
                'to' => $to,
                'subject' => $subject
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email à {to}: {error}', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     *
     * @param string $to Email du destinataire
     * @param string $resetLink Lien de réinitialisation
     * @param string $username Nom d'utilisateur
     * @param int $tokenLifetime Durée de validité du token en heures
     */
    public function sendPasswordResetEmail(string $to, string $resetLink, string $username, int $tokenLifetime = 24): void
    {
        $this->sendEmail(
            $to,
            'Réinitialisation de votre mot de passe FootConnect',
            'security/reset_password/email.html.twig',
            [
                'resetLink' => $resetLink,
                'tokenLifetime' => $tokenLifetime,
                'username' => $username
            ]
        );
    }
}