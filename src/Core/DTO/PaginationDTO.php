<?php

namespace App\Core\DTO;

class PaginationDTO
{
    public function __construct(
        public int $currentPage,
        public int $totalPages,
        public int $totalItems,
        public array $items,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'currentPage' => $this->currentPage,
            'totalPages' => $this->totalPages,
            'totalItems' => $this->totalItems,
            'items' => $this->items,
        ];
    }
}
