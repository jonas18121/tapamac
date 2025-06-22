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
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * User - Manager.
 */
class UserManager extends BaseManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private TranslatorInterface $translator,
        private ParameterBagInterface $parameters,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository
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
    
    public function create(User $user): User
    {
        $user->setCreatedAt(new \DateTimeImmutable());

        return $this->save($user);
    }

    public function update(User $user): User
    {
        $user->setUpdatedAt(new \DateTimeImmutable());

        return $this->save($user);
    }

    public function save(User $user): User
    {
        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function delete(
        User $user,
        bool $disable = false
    ): void {
        if ($disable) {
            $user->setDeletedAt((new \DateTime('now'))->setTimezone(new \DateTimeZone('UTC')));
            $this->save($user);
        } else {
            $em = $this->em();
            $em->remove($user);
            $em->flush();
        }
    }

    public function list(
        int $page, 
        string $name, 
        int $limit
    ): ?SlidingPagination
    {
        return $this->userRepository->findPaginationList($page, $name, $limit);
    }

    public function search(
        SearchData $searchData
    ): ?SlidingPagination
    {
        return $this->userRepository->findBySearch($searchData);
    }

    public function countList(
        SearchData $searchData
    ): array
    {
        $count = [
            "countTotalElementFiltered" => $this->userRepository->countTotalAndFilteredElements($searchData)
        ];

        return $count;
    }
}