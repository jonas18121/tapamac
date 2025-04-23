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

use App\Entity\Category;
use App\Entity\User;

/**
 * Category - Manager.
 */
class CategoryManager extends BaseManager
{
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
}