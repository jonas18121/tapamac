<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\PaginationTrait;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    use PaginationTrait;
    
    public function __construct(
        ManagerRegistry $registry, 
        private PaginatorInterface $paginationInterface
    )
    {
        parent::__construct($registry, Category::class);
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    // public function findListCategories(int $page): ?SlidingPagination
    // {
    //     /** @var array<int, Category> */
    //     $data = $this->createQueryBuilder('c')
    //         ->select('c')
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
