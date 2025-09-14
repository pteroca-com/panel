<?php

namespace App\Core\DTO\Pterodactyl;

use ArrayAccess;
use ReturnTypeWillChange;

class Resource implements ArrayAccess
{
    protected array $attributes;

    public function __construct(array $data = [])
    {
        // Kompatybilność z Timdesm - jeśli ma 'attributes', używaj ich
        $this->attributes = isset($data['attributes']) ? $data['attributes'] : $data;
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
        return $this->attributes;
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
}
