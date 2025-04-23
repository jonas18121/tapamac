<?php

namespace App\Repository;

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
}
