<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Mime\Email;
use Psr\Cache\CacheItemPoolInterface;
use Doctrine\ORM\Query\QueryException;
// use Doctrine\DBAL\Exception as QueryException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{
    # Définit le nombre de minutes souhaiter pour attendre avant d'envoyer un mail et un log pour une même erreur
    private const COOL_DOWN_IN_MINUTES = 1;

    # Calcule pour que COOL_DOWN_IN_MINUTES soit vraiment traduit en minitues
    private const COOL_DOWN = self::COOL_DOWN_IN_MINUTES * 60;

    private const STATUS_INTERNAL_SERVER = 500;
    private const STATUS_UNAUTHORIZED = 401;
    private const STATUS_FORBIDDEN = 403;
    private const STATUS_NOT_FOUND = 404;

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
            // 'kernel.exception' => ['onKernelException', -100],
            // ExceptionEvent::class => ['onKernelException', -100],
            // KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::EXCEPTION => ['onKernelException', -100],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        /** @var \Throwable $exceptionLogger */
        $exceptionLogger = $event->getThrowable();

        /** @var Request $request */
        $request = $event->getRequest();

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
        [$level, $logger, $text] = $this->managerException($exceptionLogger, $statusCode, $request);

        try {
            // LOG avec le niveau d'erreur déterminé
            $logger->$level($text, [
                'status_code' => $statusCode,
                'message' => $exceptionLogger->getMessage(),
                'file' => $exceptionLogger->getFile(),
                'line' => $exceptionLogger->getLine(),
                // Attention trace = beaucoup de texte, utile pour debug
                // 'trace' => $exceptionLogger->getTraceAsString(),
            ]);
        } catch (\Throwable $error) {
            # dernier rempart : empêche une boucle infinie si le logger échoue
            # Log de secours avec lastError()
            $this->lastError($exceptionLogger, $error, "Logger");
        }
    }

    /**
     * Gère les doublons des erreurs
     */
    private function managerDuplicate(\Throwable $exceptionLogger): bool
    {
        // Identifiant unique pour la déduplication
        /** @var string $idDeduplicate */
        $idDeduplicate = hash(
            'sha256', 
            $exceptionLogger::class 
            . '|' . $exceptionLogger->getMessage()
            . '|' . $exceptionLogger->getFile()
            . '|' . $exceptionLogger->getLine()
        );

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
        $statusCode = self::STATUS_INTERNAL_SERVER; // par défaut

        // Si c’est une exception HTTP, on récupère le vrai code (404, 403, 401, etc.)
        if ($exceptionLogger instanceof HttpExceptionInterface) {
            /** @var int $statusCode */
            $statusCode = $exceptionLogger->getStatusCode();
        }

        return $statusCode;
    }

    /** 
     * Contrôle l'envoi des mails : 
     *  → true : autoriser l'envoi de mail (production ou FORCE_ERROR_MAIL=true dans .env.local) 
     * → false : refuser l'envoi de mail 
     */
    private function isAllowedSendMail(): bool
    {
        # En prod : toujours envoyer un mail
        if ('prod' === $this->environment) {
            return true;
        }

        # En dev, test, autres... : envoyer un mail seulement si FORCE_ERROR_MAIL=true dans .env.local
        if (isset($_ENV['FORCE_ERROR_MAIL']) && $_ENV['FORCE_ERROR_MAIL'] === 'true') {
            return true;
        }

        # Pas d'envoie de mail
        return false;
    }

    /**
     * Détermine le type d’exception et le logger associé.
     * 
     * Exemple : 
     *     - $logger->error()
     *     - $logger->critical()
     *     - $logger->alert()
     *     - $logger->emergency()
     */
    private function managerException(\Throwable $exception, int $statusCode, ?Request $request): array
    {
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
            $this->sendEmail('EMERGENCY', $exception, $statusCode, $request);
            return ['emergency', $this->emergencyLogger, 'Fatal error'];
        }

        // ============================
        // 2. ALERT (BD, sécurité, API)
        // ============================
        if (
            $exception instanceof \PDOException ||
            $exception instanceof QueryException
        ) {
            $this->sendEmail('ALERT', $exception, $statusCode, $request);
            return ['alert', $this->alertLogger, 'Database error'];
        }

        // ============================
        // 3. CRITICAL (Erreurs serveur 500+)
        // ============================
        if ($statusCode >= self::STATUS_INTERNAL_SERVER) {
            $this->sendEmail('CRITICAL', $exception, $statusCode, $request);
            return ['critical', $this->criticalLogger, 'Server error'];
        }


        // ============================
        // 4. ERROR (Par défault)
        // ============================
        $ignoredStatusCodes = [
            self::STATUS_UNAUTHORIZED, 
            self::STATUS_FORBIDDEN, 
            self::STATUS_NOT_FOUND
        ];

        if (!in_array($statusCode, $ignoredStatusCodes, true)) {
            $this->sendEmail('ERROR', $exception, $statusCode, $request);
        }
        // Erreurs fonctionnelles ou utilisateur
        return ['error', $this->errorLogger, 'Client error'];
    }

    /**
     * Envoie un email
     */
    private function sendEmail(
        string $type, 
        \Throwable $exception, 
        int $statusCode, 
        ?Request $request
    ): void
    {
        # Pas d'envoi de mail en dev ou test sauf si on autorise avec true
        if (!$this->isAllowedSendMail()) {
            return;
        }

        $url = null !== $request
            ? $request->getSchemeAndHttpHost() . $request->getPathInfo()
            : 'N/A'
        ;

        /** @var Email $email */
        $email = (new Email())
            ->from('serveur@monsite.com')
            ->to('admin@gmail.com')
            ->subject(sprintf(
                "[%s] Nouvelle erreur détectée (%d) - Application %s - %s",
                $this->safeSubject($type),
                $statusCode,
                $this->safeSubject('Tapamac'),
                $this->safeSubject($this->environment)
            ))
            ->html(sprintf(
                "<h2>Erreur détectée de type : %s</h2>
                 <p><strong>Application :</strong> %s</p>
                 <p><strong>Environnement :</strong> %s</p>
                 <p><strong>Message :</strong> %s</p>
                 <p><strong>Status code :</strong> %s</p>
                 <p><strong>URL (sans info après le '?') :</strong> %s</p>
                 <p><strong>Fichier :</strong> %s</p>
                 <p><strong>Ligne :</strong> %d</p>
                 <pre><strong>Trace :</strong><br> %s</pre>",
                $this->htmlSpecialCharsSafe($type),
                $this->htmlSpecialCharsSafe('Tapamac'),
                $this->htmlSpecialCharsSafe($this->environment),
                $this->htmlSpecialCharsSafe($exception->getMessage()),
                $statusCode,
                $this->htmlSpecialCharsSafe($url),
                $this->htmlSpecialCharsSafe($exception->getFile()),
                $exception->getLine(),
                $this->htmlSpecialCharsSafe($exception->getTraceAsString())
            ))
        ;

        try {

            $this->mailer->send($email);
        } catch (\Throwable $error) {
            # dernier rempart : empêche une boucle infinie si le mailer échoue
            # Log de secours avec lastError()
            $this->lastError($exception, $error, "Mail");
        }
    }
   
    /**
     * Retourne true si la clé existe déjà (doublon).
     * Cette méthode ne crée PAS la clé.
     */
    private function isDuplicate(string $id): bool
    {
        /** @var string $key */
        $key = 'cool_down_' . $id;

        // Lock pour éviter les races conditions si plusieurs requête en même temps
        /** @var Lock $lock */
        $lock = $this->lockFactory->createLock($key, ttl: 5);

        // Si $lock->acquire() retourne false, considérer comme doublon
        if (!$lock->acquire()) {
            // isDuplicate retournera true
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
        $key = 'cool_down_' . $id;

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
    
            // Demande que l’item expire automatiquement après le nombre de temps définit dans COOL_DOWN . 
            // C’est la durée pendant laquelle la clé empêchera l’envoi d’un nouvel e-mail.
            $item->expiresAfter(self::COOL_DOWN);
    
            // On sauvegarde l’item dans le pool de cache. 
            // Après save() la clé existe et isHit() renverra true jusqu’à l’expiration
            $this->dedupeCache->save($item);
        }
        finally {
            // Libères le verrou, les autres processus peuvent accéder à la ressource.
            $lock->release();
        }
    }

    /**
     * Dernier rempart : écrit dans error_log() si logger ou mailer échoue.
     *
     * error_log() écrit là où PHP est configuré pour écrire les erreurs sans générer une nouvelle exception. 
     * 
     * Exemple dans serveur ubuntu ou dans le contenaire docker : cat /var/log/apache2/error.log ou /var/log/php7.x-fpm.log ou /var/log/php/error.log 
     * 
     * Exemple commande docker : docker logs <container_name>
     * 
     * cat /var/log/apache2/app_error.log
     * 
     */
    private function lastError(
        \Throwable $exception,
        \Throwable $error,
        string $name
    ): void 
    {
        @error_log(sprintf(
            "[ExceptionSubscriber CRITICAL][%s] %s failed: %s in %s:%d | Other exception: %s",
            $this->environment,
            $name,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $exception->getMessage()
        ));
    }

    private function htmlSpecialCharsSafe(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function safeSubject(string $value): string
    {
        return trim(preg_replace('/[\r\n]+/', ' ', $value));
    }
}