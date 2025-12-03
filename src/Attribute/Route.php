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

namespace Lunar\Attribute;

/**
 * Classe Route.
 *
 * Utilisée pour définir les routes via des annotations sur les méthodes des contrôleurs.
 *
 * @param string $path    le chemin de la route
 * @param array  $methods les méthodes HTTP associées (par défaut GET)
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{
    public string $path;

    /**
     * @var array<string> liste des méthodes HTTP autorisées
     */
    public array $methods;
    public ?string $name;

    /**
     * Route constructor.
     *
     * @param string        $path    the route path
     * @param array<string> $methods allowed HTTP methods (default ['GET'])
     * @param null|string   $name    optional name of the route
     */
    public function __construct(string $path, array $methods = ['GET'], ?string $name = null)
    {
        $this->path = $path;
        $this->methods = $methods;
        $this->name = $name;
    }
}
