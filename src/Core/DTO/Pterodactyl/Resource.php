<?php

namespace App\Core\DTO\Pterodactyl;

use App\Core\Contract\Pterodactyl\MetaAccessInterface;
use ArrayAccess;
use ReturnTypeWillChange;

class Resource implements ArrayAccess, MetaAccessInterface
{
    protected array $attributes = [];

    public function __construct(
        protected array $data = [],
        protected array $meta = [],
    ) {
        // Kompatybilność z Timdesm - jeśli ma 'attributes', używaj ich
        $this->attributes = isset($data['attributes']) ? $data['attributes'] : $data;

        // Przetwarzanie relationships - bezpośrednio jako właściwości główne
        if (isset($this->attributes['relationships'])) {
            foreach ($this->attributes['relationships'] as $key => &$relationship) {
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
        }
    }

    public function has(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function get(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function __get(string $name)
    {
        if ($name === 'attributes') {
            return (object) $this->attributes;
        }
        
        return $this->get($name);
    }

    public function __set(string $name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function toArray(): array
    {
        return $this->convertToArray($this->attributes);
    }

    /**
     * Rekurencyjnie konwertuje zagnieżdżone obiekty na tablice
     */
    private function convertToArray($data): array
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->convertValue($value);
            }
            return $result;
        }

        return [];
    }

    /**
     * Konwertuje pojedynczą wartość, obsługując zagnieżdżone obiekty
     */
    private function convertValue($value)
    {
        if ($value instanceof Resource) {
            return $value->toArray();
        }

        if ($value instanceof Collection) {
            return $value->toArray();
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->convertValue($item);
            }
            return $result;
        }

        return $value;
    }

    // ArrayAccess implementation - kompatybilność z oryginalną biblioteką
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    #[ReturnTypeWillChange] public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
