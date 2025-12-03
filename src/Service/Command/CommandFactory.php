<?php
/**
 * @since 0.0.1
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Command;

use Lunar\Cli\CommandFactoryInterface;
use Lunar\Service\Core\Router;

/**
 * Fabrique qui instancie dynamiquement les commandes CLI avec injection des dépendances.
 */
class CommandFactory implements CommandFactoryInterface
{
    /**
     * Liste des instances de services partagés.
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
     */
    public function make(string $className): object
    {
        $refClass = new \ReflectionClass($className);

        $constructor = $refClass->getConstructor();
        if (null === $constructor || 0 === $constructor->getNumberOfParameters()) {
            return new $className();
        }

        $params = array_map(function (\ReflectionParameter $param) {
            $type = $param->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
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
