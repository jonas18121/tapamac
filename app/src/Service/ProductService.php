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
use App\Manager\ProductManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;

/**
 * Product - Service.
 */
class ProductService
{
    public function __construct(
        private ProductManager $productManager
    ) 
    {
    }

    /** 
     * Retourne le(s) produit(s) qui sont rechercher via la barre de recherche 
     * Sinon par défaut, retourne tous les produits
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
            return $this->productManager->search($searchData);
        }
        
        // Par défaut
        return $this->productManager->list($request->query->getInt('page', 1), 'product', 3);
    }

    /** 
     * Retourne le(s) produit(s) qui sont rechercher via la barre de recherche 
     * Sinon par défaut, retourne tous les produits
     * Retourne aussi le nombre de produits trouver
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
            $productList = $this->productManager->search($searchData);
            $count = $this->productManager->countList($searchData);
            return [$count, $productList];
        }

        $count = $this->productManager->countList($searchData);
        $productList = $this->productManager->list($request->query->getInt('page', 1), 'product', 3);

        // Par défaut
        return [$count, $productList];
    }
}