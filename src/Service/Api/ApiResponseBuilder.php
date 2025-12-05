<?php

declare(strict_types=1);

namespace Lunar\Service\Api;

use Lunar\Service\Core\Http\Response;

/**
 * Builder fluent pour créer des réponses API personnalisées.
 *
 * Utilise le pattern Builder pour une création flexible de réponses
 * quand les méthodes statiques de ApiResponse ne suffisent pas.
 *
 * @example
 * ```php
 * $response = ApiResponseBuilder::create()
 *     ->success()
 *     ->data(['id' => 1, 'name' => 'John'])
 *     ->meta(['version' => '1.0'])
 *     ->header('X-Request-Id', 'abc123')
 *     ->header('Cache-Control', 'no-cache')
 *     ->status(200)
 *     ->build();
 * ```
 */
final class ApiResponseBuilder
{
    private bool $isSuccess = true;
    private mixed $data = null;
    /** @var array<string, mixed> */
    private array $meta = [];
    /** @var array<string, string> */
    private array $headers = [];
    private int $statusCode = 200;
    private ?string $errorCode = null;
    private ?string $errorMessage = null;
    /** @var array<string, mixed> */
    private array $errorDetails = [];

    private function __construct()
    {
    }

    /**
     * Crée une nouvelle instance du builder.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Définit la réponse comme succès.
     */
    public function success(): self
    {
        $this->isSuccess = true;
        return $this;
    }

    /**
     * Définit la réponse comme erreur.
     */
    public function error(string $code, string $message): self
    {
        $this->isSuccess = false;
        $this->errorCode = $code;
        $this->errorMessage = $message;
        return $this;
    }

    /**
     * Définit les données de la réponse.
     */
    public function data(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Définit les métadonnées de la réponse.
     *
     * @param array<string, mixed> $meta
     */
    public function meta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Ajoute un en-tête HTTP.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Définit le code de statut HTTP.
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Ajoute des détails à l'erreur.
     *
     * @param array<string, mixed> $details
     */
    public function errorDetails(array $details): self
    {
        $this->errorDetails = $details;
        return $this;
    }

    /**
     * Construit la réponse finale.
     */
    public function build(): Response
    {
        if ($this->isSuccess) {
            return $this->buildSuccessResponse();
        }

        return $this->buildErrorResponse();
    }

    private function buildSuccessResponse(): Response
    {
        $body = [
            'success' => true,
            'data' => $this->data,
        ];

        if (!empty($this->meta)) {
            $body['meta'] = $this->meta;
        }

        return $this->createJsonResponse($body);
    }

    private function buildErrorResponse(): Response
    {
        $error = [
            'code' => $this->errorCode,
            'message' => $this->errorMessage,
        ];

        if (!empty($this->errorDetails)) {
            $error['details'] = $this->errorDetails;
        }

        $body = [
            'success' => false,
            'error' => $error,
        ];

        return $this->createJsonResponse($body);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function createJsonResponse(array $body): Response
    {
        $json = json_encode(
            $body,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        $this->headers['Content-Type'] = 'application/json';

        return new Response($json, $this->statusCode, $this->headers);
    }
}
