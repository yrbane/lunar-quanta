<?php

declare(strict_types=1);

namespace Lunar\Service\Logging\Formatter;

/**
 * Formateur de logs en ligne de texte.
 *
 * Format par défaut (similaire à Monolog) :
 * [2025-01-15 10:30:00] app.INFO: Message {"context":"value"}
 *
 * Ce format est idéal pour :
 * - Les fichiers de log lisibles par humains
 * - L'analyse avec grep/awk/sed
 * - Les outils de monitoring basiques
 */
final class LineFormatter implements LogFormatterInterface
{
    private const DEFAULT_FORMAT = "[%datetime%] %channel%.%level%: %message% %context%\n";
    private const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private readonly string $format = self::DEFAULT_FORMAT,
        private readonly string $dateFormat = self::DEFAULT_DATE_FORMAT
    ) {
    }

    public function format(array $record): string
    {
        $output = $this->format;

        // Formatage du datetime
        $datetime = $record['datetime']->format($this->dateFormat);
        $output = str_replace('%datetime%', $datetime, $output);

        // Channel et niveau
        $output = str_replace('%channel%', $record['channel'], $output);
        $output = str_replace('%level%', strtoupper($record['level']), $output);

        // Message
        $output = str_replace('%message%', $record['message'], $output);

        // Contexte (JSON si non vide)
        $context = '';
        if (!empty($record['context'])) {
            $context = json_encode($record['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $output = str_replace('%context%', $context, $output);

        // Nettoyage des espaces superflus
        return rtrim($output) . "\n";
    }
}
