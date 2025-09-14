<?php

namespace App\Core\DTO\Pterodactyl;

use ArrayAccess;

class Collection implements ArrayAccess
{
    protected array $data;

    public function __construct(array $data = [])
    {
        // Collection zawsze przechowuje tablicę elementów
        $this->data = $data;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return $this->data;
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
}
