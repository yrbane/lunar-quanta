<?php

declare(strict_types=1);

namespace Lunar\Service\Database\Migration;

/**
 * Classe de base pour les migrations.
 *
 * Chaque migration étend cette classe et implémente les méthodes
 * up() et down() pour appliquer et annuler les changements.
 *
 * @example
 * ```php
 * // 2025_01_15_000000_create_users_table.php
 * return new class extends Migration {
 *     public function up(): void
 *     {
 *         $this->execute(Schema::create('users', function (Blueprint $table) {
 *             $table->id();
 *             $table->string('name');
 *             $table->string('email')->unique();
 *             $table->timestamps();
 *         }));
 *     }
 *
 *     public function down(): void
 *     {
 *         $this->execute(Schema::drop('users'));
 *     }
 * };
 * ```
 */
abstract class Migration
{
    /** @var array<string> */
    protected array $statements = [];

    /**
     * Exécute les changements de la migration.
     */
    abstract public function up(): void;

    /**
     * Annule les changements de la migration.
     */
    abstract public function down(): void;

    /**
     * Enregistre une instruction SQL à exécuter.
     */
    protected function execute(string $sql): void
    {
        $this->statements[] = $sql;
    }

    /**
     * Retourne les instructions SQL enregistrées.
     *
     * @return array<string>
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    /**
     * Vide les instructions enregistrées.
     */
    public function clearStatements(): void
    {
        $this->statements = [];
    }
}
