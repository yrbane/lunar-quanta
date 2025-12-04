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

namespace Lunar\Service\Core\Http;

/**
 * Classe Request.
 *
 * Encapsule les données de la requête HTTP.
 */
class Request
{
    private string $method;
    private string $uri;

    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $post;

    /** @var array<string, mixed> */
    private readonly array $server;

    /** @var array<string, mixed> */
    private array $headers;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * Constructeur.
     *
     * Initialise les données de la requête à partir des variables globales.
     */
    public function __construct()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->method = is_string($requestMethod) ? $requestMethod : 'GET';

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedPath = parse_url(is_string($requestUri) ? $requestUri : '/', PHP_URL_PATH);
        $this->uri = is_string($parsedPath) ? $parsedPath : '/';

        /** @var array<string, mixed> $query */
        $query = $_GET;
        $this->query = $query;

        /** @var array<string, mixed> $post */
        $post = $_POST;
        $this->post = $post;

        /** @var array<string, mixed> $server */
        $server = $_SERVER;
        $this->server = $server;

        /** @var array<string, mixed> $headers */
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $this->headers = $headers;
    }

    /**
     * Retourne la méthode HTTP.
     *
     * @return string la méthode HTTP
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retourne l'URI de la requête.
     *
     * @return string L'URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Retourne les paramètres GET.
     *
     * @return array<string, mixed> les paramètres GET
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * Retourne les paramètres POST.
     *
     * @return array<string, mixed> les paramètres POST
     */
    public function getPostParams(): array
    {
        return $this->post;
    }

    /**
     * Retourne les en-têtes HTTP.
     *
     * @return array<string, mixed> les en-têtes de la requête
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Retourne les var SERVER.
     *
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * Set a request attribute.
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Get a request attribute.
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Get all request attributes.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
