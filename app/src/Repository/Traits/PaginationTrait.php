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

namespace App\Repository\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;

trait PaginationTrait
{
    public function __construct(
        private PaginatorInterface $paginationInterface
    )
    {}

    public function findPaginationList(int $page, string $name): ?SlidingPagination
    {
        /** @var array */
        $data = $this->createQueryBuilder($name)
            ->select($name)
            ->getQuery()
            ->getResult();

        /** @var SlidingPagination */
        $pagination = $this->paginationInterface->paginate($data, $page, 10);

        if ($pagination instanceof SlidingPagination) {
            return $pagination;
        }

        return null;
    }
}