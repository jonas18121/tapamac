<?php

namespace App\Repository;

use App\DTO\SearchData;
use App\Entity\Product;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\PaginationTrait;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    use PaginationTrait;
    
    public function __construct(
        ManagerRegistry $registry,
        private PaginatorInterface $paginationInterface
    )
    {
        parent::__construct($registry, Product::class);
    }

    public function findBySearch(SearchData $searchData): ?SlidingPagination
    {
        /** @var array<int, Product> $data */
        $data = $this->requestPreparedProductList($searchData)
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

    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre total de produits
     * Soit depuis la recherche ou soit depuis la liste complète
     * Retourne 0 (Zero) si pas de produits
     */
    public function countTotalAndFilteredProducts(SearchData $searchData): int
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->Join('p.user', 'u')
            ->Join('p.category', 'c')
            ->addOrderBy('p.created_at', 'DESC')
        ;

        if (!empty($searchData->getQuery())) {
            $queryBuilder = $this->requestPreparedToSearch($queryBuilder, $searchData);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Requête préparer pour retourner une liste de produits
     */
    public function requestPreparedProductList(SearchData $searchData): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->Join('p.user', 'u')
            ->Join('p.category', 'c')
            ->addOrderBy('p.created_at', 'DESC');

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
                c.name LIKE :search
                OR u.firstName LIKE :search
                OR u.lastName LIKE :search
                OR p.price LIKE :search
                OR p.created_at LIKE :search
                OR p.title LIKE :search
            ')
            ->setParameter('search', "%{$searchData->getQuery()}%")
        ;
    }
}
