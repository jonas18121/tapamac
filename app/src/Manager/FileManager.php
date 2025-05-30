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

namespace App\Manager;

use DateTimeImmutable;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * File - Manager.
 */
class FileManager extends BaseManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
        private ParameterBagInterface $parameters,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private ProductRepository $productRepository
    ) 
    {
        parent::__construct(
            $entityManager,
            $validator,
            $translator,
            $parameters,
            $requestStack,
            $tokenStorage,
            $urlGenerator,
        );
    }

    /**
     * Permet de télécharger un ou plusieur fichiers
     */
    public function uploadMultipleFile(Array $files, string $relatifPathToFile): Array
    {
        $filenames = [];
        foreach ($files as $key => $file) {
            $filenames[] = $this->uploadFile($file, $relatifPathToFile, $key);
        }

        return $filenames;
    }

    /**
     * Permet de télécharger un fichier
     */
    public function uploadFile(UploadedFile $file, string $relatifPathToFile, null|int $key): string
    {
        $explodeFilename = explode('.', $file->getClientOriginalName());

        $date = new DateTimeImmutable();

        $newFilename = $explodeFilename[0] . '_' . $date->format('Ymd_His_mmm') . '.' . $file->getClientOriginalExtension();

        // Si $key contient une valeur, on rajoute la valeur de $key au nom du fichier
        if (null !== $key) {
            $newFilename =  $key . '_' . $newFilename;
        }

        $moveIn = $this->parameters->get('kernel.project_dir') . $relatifPathToFile;

        try {
            $file->move($moveIn, $newFilename);
        } 
        catch (FileException $e) {
            throw new FileException("Erreur lors de l'upload.");
        }

        return $newFilename;
    }
}