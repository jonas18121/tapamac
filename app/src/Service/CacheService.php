<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;


use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CacheService implements CacheInterface, CacheItemPoolInterface
{
    public function __construct(
        private CacheItemPoolInterface $innerPool,
        private string $cacheDir
    ) {}

    /**
     * Supprime les fichiers de clé qui ont expirées
     */
    private function pruneExpired(): void
    {
        # Enlève les slash à la fin
        /** @var string $dir */
        $dir = rtrim($this->cacheDir, DIRECTORY_SEPARATOR);

        # Stop si pas de répertoire var/cache/<env>/pools/app
        if (!is_dir($dir)) {
            return;
        }

        # Passe dans tous les fichiers présents dans toutes les sous-arborescences
        /** @var RecursiveIteratorIterator $iterator */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        # Récupère le timestamp courant (secondes Unix)
        /** @var int $now */
        $now = time();

        foreach ($iterator as $fileinfo) {

            # Ignore tout ce qui n’est pas un fichier (répertoires, liens spéciaux)
            if (!$fileinfo->isFile()) {
                continue;
            }

            /** @var string $file */
            $file = $fileinfo->getPathname();

            # Ouvre le fichier en lecture. 
            # @ supprime les warnings (fichier peut avoir été supprimé entre-temps / permissions).
            $handle = @fopen($file, 'r');
            if (!$handle) {
                continue;
            }

            # Lire uniquement les 2 premières lignes (expiration + clé)
            $expiryLine = fgets($handle);   # ligne 1 = timestamp
            $keyLine    = fgets($handle);   # ligne 2 = nom de clé
            
            fclose($handle);

            # Si la lecture d’une des deux lignes a échoué (fichier incomplet, lecture interrompue) on ignore et continue.
            if ($expiryLine === false || $keyLine === false) {
                continue;
            }

            $expiry = (int) trim($expiryLine);
            $key = trim($keyLine);

            # Vérifier si la clé correspond à nos clés de déduplication
            # str_starts_with est disponible depuis PHP 8.0. Si PHP < 8, utiliser strpos($key, 'cool_down_') === 0
            if (!str_starts_with($key, 'cool_down_')) {
                continue;
            }

            # Supprimer si expiré
            if ($expiry > 0 && $expiry < $now) {
                @unlink($file);

                # Nettoyage des dossiers vides
                $this->pruneEmptyDirectories($file);

                continue;
            }
        }
    }

    /**
     * Supprime les dossiers vides des fichiers de clé qui ont expirées en remontant l'arbre,
     * jusqu'au dossier racine pools/app.
     */
    private function pruneEmptyDirectories(string $filePath): void
    {
        $dir = dirname($filePath);

        # Sécurise la limite : on ne sort jamais de pools/app/
        $root = realpath($this->cacheDir);

        while ($dir && realpath($dir) !== $root) {

            # Si le dossier n'existe plus, arrête
            if (!is_dir($dir)) {
                break;
            }

            # Vérifie si le dossier est vide
            if (count(scandir($dir)) === 2) { # "." et ".."
                rmdir($dir);
            } else {
                break; # pas vide, stop
            }

            # Passe au dossier parent
            $dir = dirname($dir);
        }
    }

    //////////// Méthode pour le contrat avec CacheInterface /////////////////////

    public function get($key, callable $callback, float $beta = null, array &$metadata = null): mixed
    {
        $this->pruneExpired();
        return $this->innerPool->getItem($key);
    }

    public function delete(string $key): bool
    {
        return $this->innerPool->deleteItem($key);
    }

    //////////// Méthode pour le contrat avec CacheItemPoolInterface /////////////////////

    public function getItem(string $key): CacheItemInterface
    {
        $this->pruneExpired();
        return $this->innerPool->getItem($key);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->innerPool->save($item);
    }

    public function deleteItem(string $key): bool
    {
        return $this->innerPool->deleteItem($key);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->innerPool->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->innerPool->commit();
    }

    public function hasItem(string $key): bool
    {
        $this->pruneExpired();
        return $this->innerPool->hasItem($key);
    }

    public function clear(): bool
    {
        return $this->innerPool->clear();
    }

    public function deleteItems(array $keys = []): bool
    {
        return $this->innerPool->deleteItems($keys);
    }

    public function getItems(array $keys = []): iterable
    {
        $this->pruneExpired();
        return $this->innerPool->getItems($keys);
    }
}