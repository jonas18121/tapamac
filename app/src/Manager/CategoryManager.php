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
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Category - Manager.
 */
class CategoryManager extends BaseManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
        private ParameterBagInterface $parameters,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private CategoryRepository $categoryRepository
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

    public function create(Category $category, User $user): Category
    {
        $category->setCreatedAt(new \DateTimeImmutable())
            ->setUser($user)
        ;

        return $this->save($category);
    }

    public function update(Category $category): Category
    {
        $category->setUpdatedAt(new \DateTimeImmutable());

        return $this->save($category);
    }

    public function save(Category $category): Category
    {
        $em = $this->em();
        $em->persist($category);
        $em->flush();

        return $category;
    }

    public function delete(
        Category $category,
        bool $disable = false
    ): void {
        if ($disable) {
            $category->setDeletedAt((new \DateTime('now'))->setTimezone(new \DateTimeZone('UTC')));
            $this->save($category);
        } else {
            $em = $this->em();
            $em->remove($category);
            $em->flush();
        }
    }

    public function list(
        int $page, 
        string $name, 
        int $limit
    ): ?SlidingPagination
    {
        return $this->categoryRepository->findPaginationList($page, $name, $limit);
    }
}