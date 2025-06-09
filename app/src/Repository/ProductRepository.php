<?php

namespace App\Repository;

use App\DTO\SearchData;
use App\Entity\Product;
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

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // public function findListProducts(int $page): ?SlidingPagination
    // {
    //     /** @var array<int, Product> */
    //     $data = $this->createQueryBuilder('p')
    //         ->select('p')
    //         ->getQuery()
    //         ->getResult();

    //     /** @var SlidingPagination */
    //     $pagination = $this->paginationInterface->paginate($data, $page, 10);

    //     if ($pagination instanceof SlidingPagination) {
    //         return $pagination;
    //     }

    //     return null;
    // }

    public function findBySearch(SearchData $searchData): ?SlidingPagination
    {
        /** @var QueryBuilder $query */
        $query = $this->createQueryBuilder('p')
            ->select('p')
            ->Join('p.user', 'u')
            ->Join('p.category', 'c')
            ->addOrderBy('p.created_at', 'DESC');

        if (!empty($searchData->getQuery())) {
            $query = $query

                // Si l'user a écrit la catégorie d'un produit depuis l'input, on l'affiche
                ->orWhere('c.name LIKE :searchCategory')
                ->setParameter('searchCategory', "%{$searchData->getQuery()}%")

                // Si l'user a écrit le nom du créateur de l'annonce du produit depuis l'input, on l'affiche
                ->orWhere('u.firstName LIKE :searchUserFirstName')
                ->setParameter('searchUserFirstName', "%{$searchData->getQuery()}%") 

                // Si l'user a écrit le nom du créateur de l'annonce du produit depuis l'input, on l'affiche
                ->orWhere('u.lastName LIKE :searchUserLastName')
                ->setParameter('searchUserLastName', "%{$searchData->getQuery()}%") 

                // Si l'user a écrit le prix d'un produit depuis l'input, on l'affiche
                ->orWhere('p.price LIKE :searchPrice')
                ->setParameter('searchPrice', "%{$searchData->getQuery()}%")

                // Si l'user a écrit la date de créaction d'un produit depuis l'input, on l'affiche
                ->orWhere('p.created_at LIKE :searchCreatedAt')
                ->setParameter('searchCreatedAt', "%{$searchData->getQuery()}%")

                // Si l'user a écrit le titre d'un produit depuis l'input, on l'affiche
                ->orWhere('p.title LIKE :searchTitle')
                ->setParameter('searchTitle', "%{$searchData->getQuery()}%")
            ;
        }

        /** @var array<int, Product> $data */
        $data = $query
            ->getQuery()
            ->getResult();

        /** @var SlidingPagination */
        $pagination = $this->paginationInterface->paginate($data, $searchData->getPage(), 2);

        if ($pagination instanceof SlidingPagination) {
            return $pagination;
        }

        return null;
    }
}
