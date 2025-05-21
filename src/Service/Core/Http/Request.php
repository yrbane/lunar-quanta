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

namespace App\Service\Core\Http;

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

    /**
     * Constructeur.
     *
     * Initialise les données de la requête à partir des variables globales.
     */
    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
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
}
