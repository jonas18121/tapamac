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

use App\Entity\User;
use App\DTO\SearchData;
use App\Entity\Product;
use App\Manager\FileManager;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Product - Manager.
 */
class ProductManager extends BaseManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
        private ParameterBagInterface $parameters,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private ProductRepository $productRepository,
        private FileManager $fileManager
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

    public function create(Product $product, User $user): Product
    {
        $product->setCreatedAt(new \DateTimeImmutable())
            ->setUser($user)
        ;

        return $this->save($product);
    }

    public function update(Product $product): Product
    {
        $product->setUpdatedAt(new \DateTimeImmutable());

        return $this->save($product);
    }

    public function save(Product $product): Product
    {
        $em = $this->em();
        $em->persist($product);
        $em->flush();

        return $product;
    }

    public function delete(
        Product $product,
        bool $disable = false
    ): void {
        if ($disable) {
            $product->setDeletedAt((new \DateTime('now'))->setTimezone(new \DateTimeZone('UTC')));
            $this->save($product);
        } else {
            $em = $this->em();
            $em->remove($product);
            $em->flush();
        }
    }

    public function list(
        int $page, 
        string $name, 
        int $limit
    ): ?SlidingPagination
    {
        return $this->productRepository->findPaginationList($page, $name, $limit);
    }

    public function createOrUpdateWithUploadImage(
        Product $product, 
        Array $files, 
        string $relatifPathImage,
        string $methode
    ): Product
    {
        if (!empty($files)) {
            $newFilenames = $this->fileManager->uploadMultipleFile($files, $relatifPathImage);
            $product->setImages($newFilenames);
        }

        switch ($methode) {
            case 'create':
                return $this->create($product, $this->getCurrentUser());
                break;
            case 'update':
                return $this->update($product);
                break;
            default :
                throw new Exception("Aucune méthode valide n'a été indiquer pour télécharger des images.");
        }
    }

    public function search(
        SearchData $searchData
    ): ?SlidingPagination
    {
        return $this->productRepository->findBySearch($searchData);
    }

}