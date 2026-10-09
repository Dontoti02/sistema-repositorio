<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Contenedor de dependencias mínimo: singletons y bindings.
 */
final class Container
{
    private static ?self $instance = null;

    /** @var array<string,Closure(self):mixed> */
    private array $bindings = [];

    /** @var array<string,mixed> */
    private array $instances = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public static function setInstance(?self $container): void
    {
        self::$instance = $container;
    }

    /**
     * @param class-string|string $id
     * @param Closure(self):mixed $factory
     */
    public function bind(string $id, Closure $factory, bool $singleton = false): void
    {
        $this->bindings[$id] = $factory;
        if (!$singleton) {
            unset($this->instances[$id]);
        }
    }

    /**
     * @param class-string|string $id
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->bind($id, $factory, true);
    }

    /**
     * @param class-string|string $id
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }
        if (isset($this->bindings[$id])) {
            $value = ($this->bindings[$id])($this);
            $this->instances[$id] = $value;
            return $value;
        }
        if (class_exists($id)) {
            $ref = new \ReflectionClass($id);
            $ctor = $ref->getConstructor();
            if ($ctor === null) {
                $obj = new $id();
            } else {
                $args = [];
                foreach ($ctor->getParameters() as $param) {
                    $type = $param->getType();
                    if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                        $args[] = $this->get($type->getName());
                    } elseif ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        throw new RuntimeException("No se pudo resolver el parámetro \${$param->getName()} de {$id}");
                    }
                }
                $obj = $ref->newInstanceArgs($args);
            }
            $this->instances[$id] = $obj;
            return $obj;
        }
        throw new RuntimeException("Servicio no registrado: {$id}");
    }

    /**
     * @param class-string|string $id
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]) || class_exists($id);
    }
}
