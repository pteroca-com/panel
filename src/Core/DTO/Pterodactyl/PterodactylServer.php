<?php

namespace App\Core\DTO\Pterodactyl;

use App\Core\DTO\Pterodactyl\Resource;
use App\Core\DTO\Pterodactyl\Collection;

class PterodactylServer extends Resource
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Przetwarzanie relationships - bezpośrednio jako właściwości główne
        if (isset($data['relationships'])) {
            foreach ($data['relationships'] as $key => &$relationship) {
                if (!isset($relationship['data'])) {
                    $relationship['data'] = $relationship;
                }

                if (is_array($relationship['data']) && array_keys($relationship['data']) === range(0, count($relationship['data']) - 1)) {
                    // It's an array of items - tworzymy Collection
                    $resources = array_map(function($item) {
                        return new Resource($item);
                    }, $relationship['data']);
                    $relationship = new Collection($resources);
                } else {
                    // It's a single item
                    $relationship = new Resource($relationship['data']);
                }
            }
            
            // Zachowaj też oryginalną strukturę relationships dla dostępu poprzez get('relationships')
            $this->attributes['relationships'] = $data['relationships'];
        }
    }
}
