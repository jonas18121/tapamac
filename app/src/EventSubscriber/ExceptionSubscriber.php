<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Mime\Email;
use Psr\Cache\CacheItemPoolInterface;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private const DEDUPLICATE_MINUTES = 1;

    public function __construct(
        private LoggerInterface $errorLogger,
        private LoggerInterface $criticalLogger,
        private LoggerInterface $alertLogger,
        private LoggerInterface $emergencyLogger,
        private MailerInterface $mailer,
        private CacheItemPoolInterface $dedupeCache,
        private LockFactory $lockFactory,
        private string $environment
    ){
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // 'kernel.exception' => 'onKernelException',
            ExceptionEvent::class => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        /** @var \Throwable $exceptionLogger */
        $exceptionLogger = $event->getThrowable();

        // ============================
        // === GERE LES STATUS DE CODES 
        // ============================

        /** @var int $statusCode */
        $statusCode = $this->managerStatusCode($exceptionLogger);

        // ============================
        // === GERE LES DEDUPLICATIONS 
        // ============================

        // Retourne true si la clé existe déjà (doublon).
        if ($this->managerDuplicate($exceptionLogger)) {
            return; // pas d’e-mail, pas de double log
        }

        // ============================
        // === GERE LES EXCEPTIONS 
        // ============================

        // Gère le type d'erreur qui doit être utiliser par $logger et le type de logger à utiliser
        [$level, $logger, $text] = $this->managerException($exceptionLogger, $statusCode);

        // LOG avec le niveau d'erreur déterminé
        $logger->$level($text, [
            'status_code' => $statusCode,
            'message' => $exceptionLogger->getMessage(),
            'file' => $exceptionLogger->getFile(),
            'line' => $exceptionLogger->getLine(),
            // Attention trace = beaucoup de texte, utile pour debug
            // 'trace' => $exceptionLogger->getTraceAsString(),
        ]);
    }

    /**
     * Gère les doublons des erreurs
     */
    private function managerDuplicate(\Throwable $exceptionLogger): bool
    {
        // Identifiant unique pour la déduplication
        /** @var string $idDeduplicate */
        $idDeduplicate = hash('sha256', $exceptionLogger::class . '|' . $exceptionLogger->getMessage());

        // Retourne true si la clé existe déjà (doublon).
        if ($this->isDuplicate($idDeduplicate)) {
            return true; // pas d’e-mail, pas de double log
        }

        // Enregistre cette erreur pendant le nombre de minutes qu'on veut
        $this->registerError($idDeduplicate);

        // Si pas de doublon retourne false
        return false;
    }

    /**
     * Gère le code status HTTP
     */
    private function managerStatusCode(\Throwable $exceptionLogger): int
    {
        /** @var int $statusCode */
        $statusCode = 500; // par défaut

        // Si c’est une exception HTTP, on récupère le vrai code (404, 403, 401, etc.)
        if ($exceptionLogger instanceof HttpExceptionInterface) {
            /** @var int $statusCode */
            $statusCode = $exceptionLogger->getStatusCode();
        }

        return $statusCode;
    }

    /**
     * Gère le type d'erreur qui doit être utiliser par $logger
     * Et gére quel type de logger à utiliser
     * 
     * Exemple : 
     *     - $logger->error()
     *     - $logger->critical()
     *     - $logger->alert()
     *     - $logger->emergency()
     */
    private function managerException(\Throwable $exception, int $statusCode): array
    {
        /** @var string $message */
        $message = strtolower($exception->getMessage());

        // ============================
        // 1. EMERGENCY (Crash fatal)
        // ============================
        // Crash PHP fatals / erreurs irréversibles
        if (
            $exception instanceof \Error ||
            $exception instanceof \TypeError ||
            $exception instanceof \ParseError ||
            $exception instanceof \ErrorException
        ) {
            $this->sendEmail('EMERGENCY', $exception, $statusCode);
            return ['emergency', $this->emergencyLogger, 'Fatal error'];
        }

        // ============================
        // 2. ALERT (BD, sécurité, API)
        // ============================
        if (
            $exception instanceof \PDOException ||
            $exception instanceof QueryException ||
            str_contains($message, 'sql') ||
            str_contains($message, 'database') ||
            str_contains($message, 'token') ||
            str_contains($message, 'jwt') ||
            str_contains($message, 'auth') ||
            str_contains($message, 'api') ||
            str_contains($message, 'timeout') ||
            str_contains($message, 'unavailable')
        ) {
            $this->sendEmail('ALERT', $exception, $statusCode);
            return ['alert', $this->alertLogger, 'Database error'];
        }

        // ============================
        // 3. CRITICAL (Erreurs serveur 500+)
        // ============================
        if ($statusCode >= 500) {
            $this->sendEmail('CRITICAL', $exception, $statusCode);
            return ['critical', $this->criticalLogger, 'Server error'];
        }


        // ============================
        // 4. ERROR (Par défault)
        // ============================
        $this->sendEmail('ERROR', $exception, $statusCode);
        // Erreurs fonctionnelles ou utilisateur
        return ['error', $this->errorLogger, 'Client error'];
    }

    /**
     * Envoie un email
     */
    private function sendEmail(string $type, \Throwable $exception, int $statusCode, bool $allowsAllEnv = false): void
    {
        // Pas d'envoi en dev ou test sauf si on autorise avec $allowsAllEnv sur true
        if ('prod' !== $this->environment && true !== $allowsAllEnv) {
            return;
        }

        /** @var Email $email */
        $email = (new Email())
            ->from('serveur@monsite.com')
            ->to('admin@gmail.com')
            ->subject("[{$type}] Nouvelle erreur détectée ({$statusCode}) - Application Tapamac - {$this->environment}")
            ->html("
                <h2>Erreur détectée de type : {$type}</h2>
                <p><strong>Application :</strong> Tapamac</p>
                <p><strong>Environnement :</strong> {$this->environment}</p>
                <p><strong>Message :</strong> {$exception->getMessage()}</p>
                <p><strong>Status code :</strong> {$statusCode}</p>
                <p><strong>Fichier :</strong> {$exception->getFile()}</p>
                <p><strong>Ligne :</strong> {$exception->getLine()}</p>
                <pre><strong>Trace :</strong><br>{$exception->getTraceAsString()}</pre>
            ");

        $this->mailer->send($email);
    }
   
    /**
     * Retourne true si la clé existe déjà (doublon).
     * Cette méthode ne crée PAS la clé.
     */
    private function isDuplicate(string $id): bool
    {
        /** @var string $key */
        $key = 'dedupe_' . $id;

        // Lock pour éviter les races conditions si plusieurs requête en même temps
        /** @var Lock $lock */
        $lock = $this->lockFactory->createLock($key, ttl: 5);

        // On attend le lock : QUAND IL EST ACQUIS, on peut lire proprement
        $lock->acquire(true);

        if (!$lock->acquire()) {
            // Si quelqu’un d’autre est en train d'écrire → considérer comme doublon
            return true;
        }

        try {
            // retourne un objet CacheItemInterface même si la clé n’existe pas (dans ce cas isHit() sera false)
            /** @var CacheItem $item */
            $item = $this->dedupeCache->getItem($key);

            // envoie true si la valeur existe dans le cache et n’a pas expiré
            return $item->isHit();
        }
        finally {
            // Libères le verrou, les autres processus peuvent accéder à la ressource.
            $lock->release();
        }
    }

    /**
     * Enregistre la clé dans le cache (atomique si Redis)
     */
    private function registerError(string $id): void
    {
        /** @var string $key */
        $key = 'dedupe_' . $id;

        // Crée un verrou portant un nom unique
        /** @var Lock $lock */
        $lock = $this->lockFactory->createLock($key, ttl: 5);

        // Tente d’obtenir le verrou, si quelqu’un l’a déjà, on retourne FALSE
        $lock->acquire(true);

        try {
            // On récupère l’élément de cache (objet) pour cette clé — soit une nouvelle instance si la clé n’existe pas, soit l’item existant.
            /** @var CacheItem $item */
            $item = $this->dedupeCache->getItem($key);
    
            // On stocke une valeur dans l’item. // la valeur n’a pas d’importance
            $item->set(true);
    
            // Demande que l’item expire automatiquement après le nombre de temps définit dans DEDUPLICATE_MINUTES . 
            // C’est la durée pendant laquelle la clé empêchera l’envoi d’un nouvel e-mail.
            $item->expiresAfter(self::DEDUPLICATE_MINUTES * 60);
    
            // On sauvegarde l’item dans le pool de cache. 
            // Après save() la clé existe et isHit() renverra true jusqu’à l’expiration
            $this->dedupeCache->save($item);
        }
        finally {
            // Libères le verrou, les autres processus peuvent accéder à la ressource.
            $lock->release();
        }
    }
}