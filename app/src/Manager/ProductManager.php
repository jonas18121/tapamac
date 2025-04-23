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

use App\Entity\Product;
use App\Entity\User;

/**
 * Product - Manager.
 */
class ProductManager extends BaseManager
{
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
}