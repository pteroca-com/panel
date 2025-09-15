<?php

namespace App\Core\DTO\Pterodactyl;

use App\Core\Contract\Pterodactyl\MetaAccessInterface;
use ArrayAccess;

class Collection implements ArrayAccess, MetaAccessInterface
{
    public function __construct(
        protected array $data = [],
        protected array $meta = [],
    ) {
    }

    public function all(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->data as $key => $value) {
            $result[$key] = $this->convertValue($value);
        }
        return $result;
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

    public function get(int $index)
    {
        return $this->data[$index] ?? null;
    }

    public function has(int $index): bool
    {
        return isset($this->data[$index]);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function first()
    {
        return reset($this->data) ?: null;
    }

    public function last()
    {
        return end($this->data) ?: null;
    }

    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
