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
 * Classe Response.
 *
 * Encapsule la réponse HTTP et gère son envoi au client.
 */
class Response
{
    private string $content;
    private int $statusCode;
    /** @var array<int, string> */
    private array $headers;

    /**
     * Constructeur.
     *
     * @param string           $content    contenu de la réponse
     * @param int              $statusCode code de statut HTTP
     * @param array<int,string> $headers    en-têtes HTTP à envoyer
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Envoie la réponse au client.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $header) {
            header($header);
        }
        echo $this->content;
    }
}
