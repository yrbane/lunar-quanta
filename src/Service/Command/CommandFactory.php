<?php

declare(strict_types=1);

namespace App\Service\Command;

use App\Service\Core\Router;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Fabrique qui instancie dynamiquement les commandes CLI avec injection des dépendances.
 */
class CommandFactory
{
    /**
     * Liste des instances de services partagés
     *
     * @var array<class-string, object>
     */
    private array $shared = [];

    public function __construct()
    {
        // Tu peux ici déclarer des services à réutiliser (singleton)
        $this->shared[Router::class] = new Router();
    }

    /**
     * Instancie une commande avec ses dépendances injectées automatiquement.
     *
     * @param class-string $className Nom de la classe de commande
     * @return object
     */
    public function make(string $className): object
    {
        $refClass = new ReflectionClass($className);

        $constructor = $refClass->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return new $className();
        }

        $params = array_map(function (ReflectionParameter $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new \RuntimeException("Impossible d'injecter le paramètre \${$param->getName()} : type non supporté.");
            }

            $fqcn = $type->getName();

            // Retourne une instance partagée ou crée-la dynamiquement
            if (!isset($this->shared[$fqcn])) {
                $this->shared[$fqcn] = new $fqcn(); // attention : pas de dépendances récursives ici
            }

            return $this->shared[$fqcn];
        }, $constructor->getParameters());

        return $refClass->newInstanceArgs($params);
    }
}
