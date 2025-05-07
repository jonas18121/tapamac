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
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
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
}