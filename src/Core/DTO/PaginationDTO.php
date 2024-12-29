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
}
