<?php

declare(strict_types=1);

namespace Lunar\Service\Database\Migration;

/**
 * Schema builder pour les opérations de structure de base de données.
 *
 * Fournit des méthodes statiques pour créer, modifier et supprimer
 * des tables de manière expressive.
 *
 * @example
 * ```php
 * // Créer une table
 * Schema::create('users', function (Blueprint $table) {
 *     $table->id();
 *     $table->string('name');
 *     $table->string('email')->unique();
 *     $table->timestamps();
 * });
 *
 * // Supprimer une table
 * Schema::drop('users');
 *
 * // Renommer une table
 * Schema::rename('users', 'members');
 * ```
 */
final class Schema
{
    /**
     * Crée une nouvelle table.
     *
     * @param callable(Blueprint): void $callback
     * @return string Le SQL généré
     */
    public static function create(string $table, callable $callback): string
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        return $blueprint->toSql();
    }

    /**
     * Supprime une table.
     */
    public static function drop(string $table): string
    {
        return "DROP TABLE IF EXISTS {$table}";
    }

    /**
     * Supprime une table si elle existe.
     */
    public static function dropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS {$table}";
    }

    /**
     * Renomme une table.
     */
    public static function rename(string $from, string $to): string
    {
        return "ALTER TABLE {$from} RENAME TO {$to}";
    }

    /**
     * Vérifie si une table existe.
     * Retourne le SQL pour vérifier (à exécuter).
     */
    public static function hasTable(string $table): string
    {
        return "SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'";
    }

    /**
     * Vérifie si une colonne existe.
     */
    public static function hasColumn(string $table, string $column): string
    {
        return "PRAGMA table_info({$table})";
    }
}
