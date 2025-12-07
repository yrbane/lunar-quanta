<?php

declare(strict_types=1);

namespace Lunar\Service\I18n;

/**
 * Helper pour le formatage des dates.
 *
 * Fournit des formats de date localisés et des helpers pour les dates relatives.
 *
 * @example
 * ```php
 * $helper = new DateFormatHelper('fr');
 *
 * echo $helper->format($date);             // "7 décembre 2025"
 * echo $helper->formatShort($date);        // "07/12/2025"
 * echo $helper->formatRelative($date);     // "il y a 3 jours"
 * echo $helper->formatTime($date);         // "14:30"
 * ```
 */
final class DateFormatHelper
{
    private string $locale;

    /** @var array<string, array<string, string>> */
    private const FORMATS = [
        'fr' => [
            'full' => 'd F Y',
            'long' => 'l d F Y',
            'short' => 'd/m/Y',
            'numeric' => 'Y-m-d',
            'time' => 'H:i',
            'datetime' => 'd/m/Y H:i',
            'iso' => 'c',
        ],
        'en' => [
            'full' => 'F j, Y',
            'long' => 'l, F j, Y',
            'short' => 'm/d/Y',
            'numeric' => 'Y-m-d',
            'time' => 'g:i A',
            'datetime' => 'm/d/Y g:i A',
            'iso' => 'c',
        ],
    ];

    /** @var array<string, array<string, string>> */
    private const MONTHS = [
        'fr' => [
            '01' => 'janvier', '02' => 'février', '03' => 'mars',
            '04' => 'avril', '05' => 'mai', '06' => 'juin',
            '07' => 'juillet', '08' => 'août', '09' => 'septembre',
            '10' => 'octobre', '11' => 'novembre', '12' => 'décembre',
        ],
        'en' => [
            '01' => 'January', '02' => 'February', '03' => 'March',
            '04' => 'April', '05' => 'May', '06' => 'June',
            '07' => 'July', '08' => 'August', '09' => 'September',
            '10' => 'October', '11' => 'November', '12' => 'December',
        ],
    ];

    /** @var array<string, array<int, string>> */
    private const DAYS = [
        'fr' => [
            0 => 'dimanche', 1 => 'lundi', 2 => 'mardi',
            3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi',
        ],
        'en' => [
            0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday',
            3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
        ],
    ];

    public function __construct(string $locale = 'fr')
    {
        $this->locale = $locale;
    }

    /**
     * Change la locale.
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Formate une date au format complet ("7 décembre 2025").
     */
    public function format(\DateTimeInterface $date): string
    {
        return $this->formatWithLocale($date, 'full');
    }

    /**
     * Formate une date au format long ("lundi 7 décembre 2025").
     */
    public function formatLong(\DateTimeInterface $date): string
    {
        return $this->formatWithLocale($date, 'long');
    }

    /**
     * Formate une date au format court ("07/12/2025").
     */
    public function formatShort(\DateTimeInterface $date): string
    {
        return $date->format($this->getFormat('short'));
    }

    /**
     * Formate une date au format numérique ("2025-12-07").
     */
    public function formatNumeric(\DateTimeInterface $date): string
    {
        return $date->format($this->getFormat('numeric'));
    }

    /**
     * Formate une heure ("14:30").
     */
    public function formatTime(\DateTimeInterface $date): string
    {
        return $date->format($this->getFormat('time'));
    }

    /**
     * Formate une date et heure ("07/12/2025 14:30").
     */
    public function formatDateTime(\DateTimeInterface $date): string
    {
        return $this->formatWithLocale($date, 'datetime');
    }

    /**
     * Formate une date au format ISO 8601.
     */
    public function formatIso(\DateTimeInterface $date): string
    {
        return $date->format($this->getFormat('iso'));
    }

    /**
     * Formate une date de manière relative ("il y a 3 jours").
     */
    public function formatRelative(\DateTimeInterface $date, ?\DateTimeInterface $reference = null): string
    {
        $reference = $reference ?? new \DateTimeImmutable();
        $diff = $reference->diff($date);

        $isFuture = $diff->invert === 0 && $date > $reference;

        if ($diff->y > 0) {
            return $this->formatRelativeUnit($diff->y, 'year', $isFuture);
        }
        if ($diff->m > 0) {
            return $this->formatRelativeUnit($diff->m, 'month', $isFuture);
        }
        if ($diff->d > 6) {
            $weeks = (int) floor($diff->d / 7);
            return $this->formatRelativeUnit($weeks, 'week', $isFuture);
        }
        if ($diff->d > 0) {
            if ($diff->d === 1) {
                return $isFuture ? $this->getRelativeWord('tomorrow') : $this->getRelativeWord('yesterday');
            }
            return $this->formatRelativeUnit($diff->d, 'day', $isFuture);
        }
        if ($diff->h > 0) {
            return $this->formatRelativeUnit($diff->h, 'hour', $isFuture);
        }
        if ($diff->i > 0) {
            return $this->formatRelativeUnit($diff->i, 'minute', $isFuture);
        }

        return $this->getRelativeWord('now');
    }

    /**
     * Retourne "Aujourd'hui", "Hier" ou la date formatée.
     */
    public function formatSmart(\DateTimeInterface $date, ?\DateTimeInterface $reference = null): string
    {
        $reference = $reference ?? new \DateTimeImmutable();

        $dateDay = $date->format('Y-m-d');
        $today = $reference->format('Y-m-d');
        $yesterday = (clone $reference)->modify('-1 day')->format('Y-m-d');
        $tomorrow = (clone $reference)->modify('+1 day')->format('Y-m-d');

        if ($dateDay === $today) {
            return $this->getRelativeWord('today');
        }
        if ($dateDay === $yesterday) {
            return $this->getRelativeWord('yesterday');
        }
        if ($dateDay === $tomorrow) {
            return $this->getRelativeWord('tomorrow');
        }

        return $this->format($date);
    }

    /**
     * Retourne le temps de lecture estimé ("3 min de lecture").
     */
    public function formatReadingTime(int $minutes): string
    {
        if ($this->locale === 'fr') {
            if ($minutes < 1) {
                return 'Moins d\'1 min de lecture';
            }
            return $minutes . ' min de lecture';
        }

        if ($minutes < 1) {
            return 'Less than 1 min read';
        }
        return $minutes . ' min read';
    }

    /**
     * Formate avec la locale.
     */
    private function formatWithLocale(\DateTimeInterface $date, string $formatKey): string
    {
        $format = $this->getFormat($formatKey);
        $formatted = $date->format($format);

        // Remplacer les noms de mois
        $month = $date->format('m');
        if (isset(self::MONTHS[$this->locale][$month])) {
            $englishMonth = $date->format('F');
            $formatted = str_replace($englishMonth, self::MONTHS[$this->locale][$month], $formatted);
        }

        // Remplacer les noms de jours
        $dayNum = (int) $date->format('w');
        if (isset(self::DAYS[$this->locale][$dayNum])) {
            $englishDay = $date->format('l');
            $formatted = str_replace($englishDay, self::DAYS[$this->locale][$dayNum], $formatted);
        }

        return $formatted;
    }

    /**
     * Retourne le format pour la locale.
     */
    private function getFormat(string $key): string
    {
        return self::FORMATS[$this->locale][$key] ?? self::FORMATS['en'][$key];
    }

    /**
     * Formate une unité relative.
     */
    private function formatRelativeUnit(int $value, string $unit, bool $isFuture): string
    {
        $units = [
            'fr' => [
                'year' => ['an', 'ans'],
                'month' => ['mois', 'mois'],
                'week' => ['semaine', 'semaines'],
                'day' => ['jour', 'jours'],
                'hour' => ['heure', 'heures'],
                'minute' => ['minute', 'minutes'],
            ],
            'en' => [
                'year' => ['year', 'years'],
                'month' => ['month', 'months'],
                'week' => ['week', 'weeks'],
                'day' => ['day', 'days'],
                'hour' => ['hour', 'hours'],
                'minute' => ['minute', 'minutes'],
            ],
        ];

        $unitNames = $units[$this->locale][$unit] ?? $units['en'][$unit];
        $unitName = $value === 1 ? $unitNames[0] : $unitNames[1];

        if ($this->locale === 'fr') {
            if ($isFuture) {
                return "dans {$value} {$unitName}";
            }
            return "il y a {$value} {$unitName}";
        }

        if ($isFuture) {
            return "in {$value} {$unitName}";
        }
        return "{$value} {$unitName} ago";
    }

    /**
     * Retourne un mot relatif localisé.
     */
    private function getRelativeWord(string $key): string
    {
        $words = [
            'fr' => [
                'now' => "à l'instant",
                'today' => "Aujourd'hui",
                'yesterday' => 'Hier',
                'tomorrow' => 'Demain',
            ],
            'en' => [
                'now' => 'just now',
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'tomorrow' => 'Tomorrow',
            ],
        ];

        return $words[$this->locale][$key] ?? $words['en'][$key];
    }
}
