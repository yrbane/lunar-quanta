<?php

declare(strict_types=1);

namespace Lunar\Service\Logging;

/**
 * Niveaux de log conformes à PSR-3.
 *
 * Ces niveaux sont ordonnés par sévérité décroissante :
 * EMERGENCY > ALERT > CRITICAL > ERROR > WARNING > NOTICE > INFO > DEBUG
 *
 * Chaque niveau a un cas d'usage spécifique :
 * - EMERGENCY : Système inutilisable (ex: crash complet)
 * - ALERT     : Action immédiate requise (ex: base de données down)
 * - CRITICAL  : Conditions critiques (ex: composant indisponible)
 * - ERROR     : Erreurs d'exécution (ex: exception non gérée)
 * - WARNING   : Conditions exceptionnelles non-erreurs (ex: API dépréciée)
 * - NOTICE    : Événements normaux mais significatifs (ex: user login)
 * - INFO      : Événements informatifs (ex: démarrage service)
 * - DEBUG     : Informations détaillées pour debug
 *
 * @see https://www.php-fig.org/psr/psr-3/#5-psrlogloglevel
 */
final class LogLevel
{
    public const EMERGENCY = 'emergency';
    public const ALERT     = 'alert';
    public const CRITICAL  = 'critical';
    public const ERROR     = 'error';
    public const WARNING   = 'warning';
    public const NOTICE    = 'notice';
    public const INFO      = 'info';
    public const DEBUG     = 'debug';

    /**
     * Ordre de sévérité des niveaux (plus élevé = plus sévère).
     */
    private const SEVERITY = [
        self::DEBUG     => 0,
        self::INFO      => 1,
        self::NOTICE    => 2,
        self::WARNING   => 3,
        self::ERROR     => 4,
        self::CRITICAL  => 5,
        self::ALERT     => 6,
        self::EMERGENCY => 7,
    ];

    /**
     * Tous les niveaux valides.
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return array_keys(self::SEVERITY);
    }

    /**
     * Vérifie si un niveau est valide.
     */
    public static function isValid(string $level): bool
    {
        return isset(self::SEVERITY[$level]);
    }

    /**
     * Retourne la sévérité numérique d'un niveau.
     */
    public static function getSeverity(string $level): int
    {
        if (!self::isValid($level)) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        return self::SEVERITY[$level];
    }

    /**
     * Compare deux niveaux.
     *
     * @return int -1 si $a < $b, 0 si égaux, 1 si $a > $b
     */
    public static function compare(string $a, string $b): int
    {
        return self::getSeverity($a) <=> self::getSeverity($b);
    }

    /**
     * Vérifie si le niveau $level est au moins aussi sévère que $minimum.
     */
    public static function isAtLeast(string $level, string $minimum): bool
    {
        return self::getSeverity($level) >= self::getSeverity($minimum);
    }
}
