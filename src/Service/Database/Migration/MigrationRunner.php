<?php

declare(strict_types=1);

namespace Lunar\Service\Database\Migration;

/**
 * Exécuteur de migrations.
 *
 * Gère la découverte, l'exécution et le suivi des migrations.
 *
 * @example
 * ```php
 * $runner = new MigrationRunner('/path/to/migrations');
 *
 * // Récupérer les migrations en attente
 * $alreadyRun = ['2025_01_01_000000_create_users_table'];
 * $pending = $runner->getPendingMigrations($alreadyRun);
 *
 * // Charger et exécuter une migration
 * $migration = $runner->loadMigration('2025_01_02_000000_create_posts_table');
 * $migration->up();
 * ```
 */
final class MigrationRunner
{
    public function __construct(
        private readonly string $migrationsPath
    ) {
    }

    /**
     * Récupère les migrations en attente d'exécution.
     *
     * @param array<string> $alreadyRun Noms des migrations déjà exécutées
     * @return array<string> Noms des migrations en attente (triées par nom)
     */
    public function getPendingMigrations(array $alreadyRun): array
    {
        $all = $this->getAllMigrations();
        $pending = array_diff($all, $alreadyRun);

        sort($pending);

        return array_values($pending);
    }

    /**
     * Récupère toutes les migrations disponibles.
     *
     * @return array<string>
     */
    public function getAllMigrations(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        if ($files === false) {
            return [];
        }

        $migrations = [];
        foreach ($files as $file) {
            $name = $this->getMigrationName($file);
            if ($name !== null) {
                $migrations[] = $name;
            }
        }

        sort($migrations);

        return $migrations;
    }

    /**
     * Charge une migration par son nom.
     */
    public function loadMigration(string $name): Migration
    {
        $file = $this->migrationsPath . '/' . $name . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }

        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new \RuntimeException(
                "Migration file must return an instance of Migration: {$file}"
            );
        }

        return $migration;
    }

    /**
     * Extrait le nom de la migration depuis le chemin du fichier.
     */
    private function getMigrationName(string $path): ?string
    {
        $filename = basename($path, '.php');

        // Format attendu : YYYY_MM_DD_HHMMSS_description
        if (!preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_/', $filename)) {
            return null;
        }

        return $filename;
    }

    /**
     * Génère un nom de fichier de migration.
     */
    public static function generateMigrationName(string $description): string
    {
        $timestamp = date('Y_m_d_His');
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $description));
        $slug = trim($slug, '_');

        return "{$timestamp}_{$slug}";
    }
}
