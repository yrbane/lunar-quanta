<?php
/**
 *
 * @since 0.0.1
 * @link https://nethttp.net
 * @Author seb@nethttp.net
 *
 *
 */
declare(strict_types=1);

namespace Lunar\Service\Core;

/**
 * Conteneur de services ultra-léger avec résolution récursive.
 */
class Container
{
    /**
     * Services instanciés (singletons).
     *
     * @var array<class-string, object>
     */
    private array $instances = [];

    /**
     * Instancie une classe en résolvant récursivement ses dépendances.
     *
     * @param class-string $className
     */
    public function get(string $className): object
    {
        if (isset($this->instances[$className])) {
            return $this->instances[$className];
        }

        $refClass = new \ReflectionClass($className);

        if (!$refClass->isInstantiable()) {
            throw new \RuntimeException("La classe {$className} n’est pas instanciable.");
        }

        $constructor = $refClass->getConstructor();
        if (is_null($constructor)) {
            $instance = new $className();
            $this->instances[$className] = $instance;

            return $instance;
        }

        $args = array_map(function (\ReflectionParameter $param) use ($className) {
            $type = $param->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                throw new \RuntimeException(
                    "Impossible de résoudre la dépendance `{$param->getName()}` dans {$className}."
                );
            }

            $dependencyClass = $type->getName();

            // @var class-string $dependencyClass
            return $this->get($dependencyClass); // récursivité ici
        }, $constructor->getParameters());

        $instance = $refClass->newInstanceArgs($args);
        $this->instances[$className] = $instance;

        return $instance;
    }
}
