<?php
/**
 * @since 1.1.0
 * @link https://nethttp.net
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Service\Security\Auth;

/**
 * Password hasher using PHP's password_hash with bcrypt/argon2.
 */
class PasswordHasher implements PasswordHasherInterface
{
    private string $algorithm;
    /** @var array<string, mixed> */
    private array $options;

    /**
     * Create a password hasher.
     *
     * @param string $algorithm PASSWORD_BCRYPT, PASSWORD_ARGON2ID, etc.
     * @param array<string, mixed> $options Algorithm-specific options
     */
    public function __construct(
        string $algorithm = PASSWORD_BCRYPT,
        array $options = []
    ) {
        $this->algorithm = $algorithm;
        $this->options = $options;
    }

    public function hash(string $plainPassword): string
    {
        if ('' === $plainPassword) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }

        return password_hash($plainPassword, $this->algorithm, $this->options);
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        if ('' === $plainPassword || '' === $hashedPassword) {
            return false;
        }

        return password_verify($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, $this->algorithm, $this->options);
    }

    /**
     * Create a hasher using Argon2id algorithm.
     */
    public static function argon2id(
        int $memoryCost = PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        int $timeCost = PASSWORD_ARGON2_DEFAULT_TIME_COST,
        int $threads = PASSWORD_ARGON2_DEFAULT_THREADS
    ): self {
        return new self(PASSWORD_ARGON2ID, [
            'memory_cost' => $memoryCost,
            'time_cost' => $timeCost,
            'threads' => $threads,
        ]);
    }

    /**
     * Create a hasher using bcrypt algorithm.
     */
    public static function bcrypt(int $cost = PASSWORD_BCRYPT_DEFAULT_COST): self
    {
        return new self(PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}
