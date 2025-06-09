<?php

declare(strict_types=1);

namespace App\DTO;

class SearchData
{
    private int $page = 1;

    private ?string $query = '';

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }
}