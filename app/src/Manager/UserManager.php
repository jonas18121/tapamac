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

/**
 * User - Manager.
 */
class UserManager extends BaseManager
{
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
}