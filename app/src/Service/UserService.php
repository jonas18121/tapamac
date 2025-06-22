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

namespace App\Service;

use App\DTO\SearchData;
use App\Manager\UserManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;

/**
 * User - Service.
 */
class UserService
{
    public function __construct(
        private UserManager $userManager
    ) 
    {
    }

    /** 
     * Retourne le(s) user(s) qui sont rechercher via la barre de recherche 
     * Sinon par défaut, retourne tous les users
    */
    public function getList(
        Form $formSearch, 
        SearchData $searchData, 
        Request $request
    ): ?SlidingPagination
    {
        $formSearch->handleRequest($request);

        // Searchbar
        if($formSearch->isSubmitted() && $formSearch->isValid()){
            $searchData->setPage($request->query->getInt('page', 1));
            return $this->userManager->search($searchData);
        }
        
        // Par défaut
        return $this->userManager->list($request->query->getInt('page', 1), 'product', 3);
    }

    /** 
     * Retourne le(s) user(s) qui sont rechercher via la barre de recherche 
     * Sinon par défaut, retourne tous les users
     * Retourne aussi le nombre de users trouver
    */
    public function getListAndCount(
        Form $formSearch, 
        SearchData $searchData, 
        Request $request
    ): array
    {
        $formSearch->handleRequest($request);

        // Searchbar
        if($formSearch->isSubmitted() && $formSearch->isValid()){
            $searchData->setPage($request->query->getInt('page', 1));
            $list = $this->userManager->search($searchData);
            $count = $this->userManager->countList($searchData);
            return [$count, $list];
        }

        $count = $this->userManager->countList($searchData);
        $list = $this->userManager->list($request->query->getInt('page', 1), 'user', 3);

        // Par défaut
        return [$count, $list];
    }
}