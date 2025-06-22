<?php

namespace App\Repository;

use App\Entity\User;
use App\DTO\SearchData;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\PaginationTrait;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    use PaginationTrait;

    public function __construct(
        ManagerRegistry $registry,
        private PaginatorInterface $paginationInterface
    )
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findBySearch(SearchData $searchData): ?SlidingPagination
    {
        /** @var array<int,User> $data */
        $data = $this->requestPreparedElementList($searchData)
            ->getQuery()
            ->getResult()
        ;

        /** @var SlidingPagination $pagination */
        $pagination = $this->paginationInterface->paginate($data, $searchData->getPage(), 2);

        if ($pagination instanceof SlidingPagination) {
            return $pagination;
        }

        return null;
    }

    /**
     * Compte le nombre total d'éléments
     * Soit depuis la recherche ou soit depuis la liste complète
     * Retourne 0 (Zero) si pas de éléments
     */
    public function countTotalAndFilteredElements(SearchData $searchData): int
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->addOrderBy('u.created_at', 'DESC')
            ->andWhere('u.isVerified = 1')
        ;

        if (!empty($searchData->getQuery())) {
            $queryBuilder = $this->requestPreparedToSearch($queryBuilder, $searchData);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Requête préparer pour retourner une liste d'éléments
     */
    public function requestPreparedElementList(SearchData $searchData): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u')
            ->addOrderBy('u.created_at', 'DESC');

        if (!empty($searchData->getQuery())) {
            $queryBuilder = $this->requestPreparedToSearch($queryBuilder, $searchData);
        }

        return $queryBuilder;
    }

    /**
     * Requête préparer pour retourner la valeur d'un de ces colonnes de la BDD depuis l'input search   
     */
    public function requestPreparedToSearch(QueryBuilder $queryBuilder, SearchData $searchData): QueryBuilder
    {
        return $queryBuilder
            ->andWhere('
                u.phoneNumber LIKE :search
                OR u.firstName LIKE :search
                OR u.lastName LIKE :search
                OR u.email LIKE :search
                OR u.created_at LIKE :search
                OR u.gender LIKE :search
                OR u.situation LIKE :search
            ')
            ->setParameter('search', "%{$searchData->getQuery()}%")
        ;
    }
}
