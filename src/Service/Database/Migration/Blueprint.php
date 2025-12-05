<?php

declare(strict_types=1);

namespace Lunar\Service\Database\Migration;

/**
 * Blueprint pour définir la structure d'une table.
 *
 * Inspiré de Laravel, permet de définir les colonnes, index,
 * et contraintes d'une table de manière fluide.
 *
 * @example
 * ```php
 * $blueprint = new Blueprint('users');
 * $blueprint->id();
 * $blueprint->string('name', 100);
 * $blueprint->string('email')->unique();
 * $blueprint->boolean('is_active')->default(true);
 * $blueprint->timestamps();
 *
 * echo $blueprint->toSql();
 * ```
 */
final class Blueprint
{
    /** @var array<array<string, mixed>> */
    private array $columns = [];

    /** @var array<string> */
    private array $indexes = [];

    /** @var array<array<string, mixed>> */
    private array $foreignKeys = [];

    private ?ForeignKeyDefinition $currentForeignKey = null;

    public function __construct(
        private readonly string $table
    ) {
    }

    // =========================================================================
    // Column types
    // =========================================================================

    /**
     * Auto-incrementing primary key.
     */
    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->addColumn($column, 'INTEGER', [
            'autoIncrement' => true,
            'primary' => true,
        ]);
    }

    /**
     * UUID column.
     */
    public function uuid(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'VARCHAR(36)');
    }

    /**
     * String column (VARCHAR).
     */
    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($column, "VARCHAR({$length})");
    }

    /**
     * Text column.
     */
    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TEXT');
    }

    /**
     * Integer column.
     */
    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'INTEGER');
    }

    /**
     * Big integer column.
     */
    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'BIGINT');
    }

    /**
     * Float column.
     */
    public function float(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'REAL');
    }

    /**
     * Decimal column.
     */
    public function decimal(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($column, "DECIMAL({$precision},{$scale})");
    }

    /**
     * Boolean column.
     */
    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'BOOLEAN');
    }

    /**
     * Date column.
     */
    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'DATE');
    }

    /**
     * DateTime column.
     */
    public function dateTime(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'DATETIME');
    }

    /**
     * Timestamp column.
     */
    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TIMESTAMP');
    }

    /**
     * Created_at and updated_at timestamps.
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /**
     * JSON column.
     */
    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'JSON');
    }

    /**
     * Blob column.
     */
    public function blob(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'BLOB');
    }

    // =========================================================================
    // Indexes
    // =========================================================================

    /**
     * Add index on column(s).
     *
     * @param string|array<string> $columns
     */
    public function index(string|array $columns, ?string $name = null): self
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $indexName = $name ?? $this->table . '_' . implode('_', $cols) . '_index';

        $this->indexes[] = sprintf(
            'CREATE INDEX %s ON %s (%s)',
            $indexName,
            $this->table,
            implode(', ', $cols)
        );

        return $this;
    }

    /**
     * Add unique index on column(s).
     *
     * @param string|array<string> $columns
     */
    public function unique(string|array $columns, ?string $name = null): self
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $indexName = $name ?? $this->table . '_' . implode('_', $cols) . '_unique';

        $this->indexes[] = sprintf(
            'CREATE UNIQUE INDEX %s ON %s (%s)',
            $indexName,
            $this->table,
            implode(', ', $cols)
        );

        return $this;
    }

    // =========================================================================
    // Foreign keys
    // =========================================================================

    /**
     * Add foreign key.
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $fk = new ForeignKeyDefinition($column);
        $this->currentForeignKey = $fk;
        $this->foreignKeys[] = &$fk;
        return $fk;
    }

    // =========================================================================
    // SQL generation
    // =========================================================================

    /**
     * Generate CREATE TABLE SQL.
     */
    public function toSql(): string
    {
        $columnDefs = [];

        foreach ($this->columns as $col) {
            $def = $col['name'] . ' ' . $col['type'];

            if (!empty($col['primary'])) {
                $def .= ' PRIMARY KEY';
            }

            if (!empty($col['autoIncrement'])) {
                $def .= ' AUTOINCREMENT';
            }

            if (!isset($col['nullable']) || !$col['nullable']) {
                $def .= ' NOT NULL';
            }

            if (array_key_exists('default', $col)) {
                $default = $col['default'];
                if (is_string($default)) {
                    $def .= " DEFAULT '{$default}'";
                } elseif (is_bool($default)) {
                    $def .= ' DEFAULT ' . ($default ? '1' : '0');
                } elseif ($default === null) {
                    $def .= ' DEFAULT NULL';
                } else {
                    $def .= " DEFAULT {$default}";
                }
            }

            if (!empty($col['unique'])) {
                $def .= ' UNIQUE';
            }

            $columnDefs[] = $def;
        }

        // Foreign keys
        foreach ($this->foreignKeys as $fk) {
            if ($fk instanceof ForeignKeyDefinition) {
                $fkDef = sprintf(
                    'FOREIGN KEY (%s) REFERENCES %s (%s)',
                    $fk->getColumn(),
                    $fk->getTable(),
                    $fk->getReferencedColumn()
                );

                if ($fk->getOnDelete()) {
                    $fkDef .= ' ON DELETE ' . $fk->getOnDelete();
                }

                if ($fk->getOnUpdate()) {
                    $fkDef .= ' ON UPDATE ' . $fk->getOnUpdate();
                }

                $columnDefs[] = $fkDef;
            }
        }

        $sql = sprintf(
            "CREATE TABLE %s (\n    %s\n)",
            $this->table,
            implode(",\n    ", $columnDefs)
        );

        // Indexes (separate statements)
        foreach ($this->indexes as $indexSql) {
            $sql .= ";\n" . $indexSql;
        }

        return $sql;
    }

    // =========================================================================
    // Private methods
    // =========================================================================

    /**
     * @param array<string, mixed> $options
     */
    private function addColumn(string $name, string $type, array $options = []): ColumnDefinition
    {
        $column = array_merge(['name' => $name, 'type' => $type], $options);
        $this->columns[] = &$column;

        return new ColumnDefinition($column);
    }
}

/**
 * Définition d'une colonne (fluent interface).
 */
final class ColumnDefinition
{
    /**
     * @param array<string, mixed> $column
     */
    public function __construct(
        private array &$column
    ) {
    }

    public function nullable(): self
    {
        $this->column['nullable'] = true;
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->column['default'] = $value;
        return $this;
    }

    public function unique(): self
    {
        $this->column['unique'] = true;
        return $this;
    }

    public function primary(): self
    {
        $this->column['primary'] = true;
        return $this;
    }

    public function index(): self
    {
        $this->column['index'] = true;
        return $this;
    }
}

/**
 * Définition d'une clé étrangère.
 */
final class ForeignKeyDefinition
{
    private string $column;
    private string $referencedTable = '';
    private string $referencedColumn = '';
    private ?string $onDelete = null;
    private ?string $onUpdate = null;

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->referencedColumn = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->referencedTable = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);
        return $this;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getTable(): string
    {
        return $this->referencedTable;
    }

    public function getReferencedColumn(): string
    {
        return $this->referencedColumn;
    }

    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }
}
