<?php
/**
 * Lunar Quanta Framework - Exception OAuth.
 *
 * @package    Lunar\Service\Security\OAuth
 * @author     Seb <seb@nethttp.net>
 * @copyright  2024 Lunar Quanta
 * @license    MIT License
 */
declare(strict_types=1);

namespace Lunar\Service\Security\OAuth;

/**
 * Exception levée lors d'erreurs OAuth.
 */
class OAuthException extends \Exception
{
    private ?string $errorCode;
    private ?string $errorDescription;

    public function __construct(
        string $message,
        ?string $errorCode = null,
        ?string $errorDescription = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorDescription = $errorDescription;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    /**
     * Crée une exception depuis une réponse d'erreur OAuth.
     *
     * @param array<string, mixed> $data La réponse d'erreur
     */
    public static function fromResponse(array $data): self
    {
        $error = $data['error'] ?? 'unknown_error';
        $description = $data['error_description'] ?? 'An unknown error occurred';

        return new self(
            "OAuth error: {$error} - {$description}",
            $error,
            $description
        );
    }
}
