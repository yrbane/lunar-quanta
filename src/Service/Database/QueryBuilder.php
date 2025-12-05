<?php

declare(strict_types=1);

namespace Lunar\Service\Database;

/**
 * Query Builder pour construire des requêtes SQL.
 *
 * Permet de construire des requêtes SELECT, INSERT, UPDATE, DELETE
 * de manière fluide et sécurisée (prepared statements).
 *
 * **Sécurité** : Toutes les valeurs sont passées comme paramètres
 * préparés, empêchant les injections SQL.
 *
 * @example
 * ```php
 * // SELECT avec conditions
 * $qb = new QueryBuilder();
 * $qb->select('id', 'name')
 *    ->from('users')
 *    ->where('status', '=', 'active')
 *    ->orderBy('name')
 *    ->limit(10);
 *
 * $sql = $qb->toSql();        // "SELECT id, name FROM users WHERE..."
 * $params = $qb->getParameters(); // ['active']
 *
 * // INSERT
 * $qb->insert('users')->values(['name' => 'John', 'email' => 'john@example.com']);
 *
 * // UPDATE
 * $qb->update('users')->set(['status' => 'inactive'])->where('id', '=', 1);
 *
 * // DELETE
 * $qb->delete('users')->where('id', '=', 1);
 * ```
 */
final class QueryBuilder
{
    private string $type = 'SELECT';

    /** @var array<string> */
    private array $columns = ['*'];

    private string $table = '';
    private ?string $tableAlias = null;

    /** @var array<array{type: string, table: string, alias: string, condition: string}> */
    private array $joins = [];

    /** @var array<array{type: string, column: string, operator: string, value: mixed, raw: bool}> */
    private array $wheres = [];

    /** @var array<string> */
    private array $groupBy = [];

    /** @var array<array{column: string, operator: string, value: mixed}> */
    private array $having = [];

    /** @var array<array{column: string, direction: string}> */
    private array $orderBy = [];

    private ?int $limit = null;
    private ?int $offset = null;

    /** @var array<string, mixed> */
    private array $insertValues = [];

    /** @var array<string, mixed> */
    private array $updateValues = [];

    /** @var array<mixed> */
    private array $parameters = [];

    private bool $isRawSelect = false;
    private string $rawSelect = '';

    // =========================================================================
    // SELECT
    // =========================================================================

    /**
     * Définit les colonnes à sélectionner.
     */
    public function select(string ...$columns): self
    {
        $this->type = 'SELECT';
        $this->columns = $columns ?: ['*'];
        $this->isRawSelect = false;
        return $this;
    }

    /**
     * Sélection brute (expressions SQL).
     */
    public function selectRaw(string $expression): self
    {
        $this->type = 'SELECT';
        $this->isRawSelect = true;
        $this->rawSelect = $expression;
        return $this;
    }

    /**
     * Définit la table source.
     */
    public function from(string $table, ?string $alias = null): self
    {
        $this->table = $table;
        $this->tableAlias = $alias;
        return $this;
    }

    // =========================================================================
    // WHERE
    // =========================================================================

    /**
     * Ajoute une condition WHERE (AND).
     */
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'raw' => false,
        ];
        return $this;
    }

    /**
     * Ajoute une condition WHERE (OR).
     */
    public function orWhere(string $column, string $operator, mixed $value): self
    {
        $this->wheres[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'raw' => false,
        ];
        return $this;
    }

    /**
     * WHERE IN.
     *
     * @param array<mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
            'value' => $values,
            'raw' => false,
        ];
        return $this;
    }

    /**
     * WHERE NOT IN.
     *
     * @param array<mixed> $values
     */
    public function whereNotIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'NOT IN',
            'value' => $values,
            'raw' => false,
        ];
        return $this;
    }

    /**
     * WHERE IS NULL.
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NULL',
            'value' => null,
            'raw' => false,
        ];
        return $this;
    }

    /**
     * WHERE IS NOT NULL.
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT NULL',
            'value' => null,
            'raw' => false,
        ];
        return $this;
    }

    /**
     * WHERE BETWEEN.
     */
    public function whereBetween(string $column, mixed $min, mixed $max): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'BETWEEN',
            'value' => [$min, $max],
            'raw' => false,
        ];
        return $this;
    }

    /**
     * WHERE LIKE.
     */
    public function whereLike(string $column, string $pattern): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'LIKE',
            'value' => $pattern,
            'raw' => false,
        ];
        return $this;
    }

    /**
     * WHERE brut (expression SQL).
     *
     * @param array<mixed> $parameters
     */
    public function whereRaw(string $expression, array $parameters = []): self
    {
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $expression,
            'operator' => 'RAW',
            'value' => $parameters,
            'raw' => true,
        ];
        return $this;
    }

    // =========================================================================
    // JOIN
    // =========================================================================

    /**
     * INNER JOIN.
     */
    public function join(string $table, string $alias, string $condition): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'alias' => $alias,
            'condition' => $condition,
        ];
        return $this;
    }

    /**
     * LEFT JOIN.
     */
    public function leftJoin(string $table, string $alias, string $condition): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'alias' => $alias,
            'condition' => $condition,
        ];
        return $this;
    }

    /**
     * RIGHT JOIN.
     */
    public function rightJoin(string $table, string $alias, string $condition): self
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $table,
            'alias' => $alias,
            'condition' => $condition,
        ];
        return $this;
    }

    // =========================================================================
    // GROUP BY / HAVING
    // =========================================================================

    /**
     * GROUP BY.
     */
    public function groupBy(string ...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * HAVING.
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    // =========================================================================
    // ORDER BY / LIMIT / OFFSET
    // =========================================================================

    /**
     * ORDER BY.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];
        return $this;
    }

    /**
     * LIMIT.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * OFFSET.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // =========================================================================
    // INSERT
    // =========================================================================

    /**
     * INSERT INTO.
     */
    public function insert(string $table): self
    {
        $this->type = 'INSERT';
        $this->table = $table;
        return $this;
    }

    /**
     * Valeurs à insérer.
     *
     * @param array<string, mixed> $values
     */
    public function values(array $values): self
    {
        $this->insertValues = $values;
        return $this;
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /**
     * UPDATE.
     */
    public function update(string $table): self
    {
        $this->type = 'UPDATE';
        $this->table = $table;
        return $this;
    }

    /**
     * Valeurs à mettre à jour.
     *
     * @param array<string, mixed> $values
     */
    public function set(array $values): self
    {
        $this->updateValues = $values;
        return $this;
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /**
     * DELETE FROM.
     */
    public function delete(string $table): self
    {
        $this->type = 'DELETE';
        $this->table = $table;
        return $this;
    }

    // =========================================================================
    // Build SQL
    // =========================================================================

    /**
     * Génère la requête SQL.
     */
    public function toSql(): string
    {
        $this->parameters = [];

        return match ($this->type) {
            'SELECT' => $this->buildSelect(),
            'INSERT' => $this->buildInsert(),
            'UPDATE' => $this->buildUpdate(),
            'DELETE' => $this->buildDelete(),
            default => throw new \RuntimeException("Unknown query type: {$this->type}"),
        };
    }

    /**
     * Retourne les paramètres de la requête.
     *
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    // =========================================================================
    // Private build methods
    // =========================================================================

    private function buildSelect(): string
    {
        $sql = 'SELECT ';

        if ($this->isRawSelect) {
            $sql .= $this->rawSelect;
        } else {
            $sql .= implode(', ', $this->columns);
        }

        $sql .= ' FROM ' . $this->table;

        if ($this->tableAlias !== null) {
            $sql .= ' AS ' . $this->tableAlias;
        }

        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();
        $sql .= $this->buildGroupBy();
        $sql .= $this->buildHaving();
        $sql .= $this->buildOrderBy();
        $sql .= $this->buildLimit();

        return $sql;
    }

    private function buildInsert(): string
    {
        $columns = array_keys($this->insertValues);
        $placeholders = array_fill(0, count($columns), '?');

        $this->parameters = array_values($this->insertValues);

        return sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
    }

    private function buildUpdate(): string
    {
        $sets = [];
        foreach ($this->updateValues as $column => $value) {
            $sets[] = "{$column} = ?";
            $this->parameters[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s',
            $this->table,
            implode(', ', $sets)
        );

        $sql .= $this->buildWhere();

        return $sql;
    }

    private function buildDelete(): string
    {
        $sql = 'DELETE FROM ' . $this->table;
        $sql .= $this->buildWhere();
        return $sql;
    }

    private function buildJoins(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';
        foreach ($this->joins as $join) {
            $sql .= sprintf(
                ' %s JOIN %s AS %s ON %s',
                $join['type'],
                $join['table'],
                $join['alias'],
                $join['condition']
            );
        }

        return $sql;
    }

    private function buildWhere(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];
        foreach ($this->wheres as $i => $where) {
            $condition = '';

            if ($i > 0) {
                $condition = $where['type'] . ' ';
            }

            if ($where['raw']) {
                $condition .= $where['column'];
                foreach ($where['value'] as $param) {
                    $this->parameters[] = $param;
                }
            } elseif ($where['operator'] === 'IS NULL') {
                $condition .= "{$where['column']} IS NULL";
            } elseif ($where['operator'] === 'IS NOT NULL') {
                $condition .= "{$where['column']} IS NOT NULL";
            } elseif ($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') {
                $placeholders = array_fill(0, count($where['value']), '?');
                $condition .= sprintf(
                    '%s %s (%s)',
                    $where['column'],
                    $where['operator'],
                    implode(', ', $placeholders)
                );
                foreach ($where['value'] as $v) {
                    $this->parameters[] = $v;
                }
            } elseif ($where['operator'] === 'BETWEEN') {
                $condition .= "{$where['column']} BETWEEN ? AND ?";
                $this->parameters[] = $where['value'][0];
                $this->parameters[] = $where['value'][1];
            } else {
                $condition .= "{$where['column']} {$where['operator']} ?";
                $this->parameters[] = $where['value'];
            }

            $conditions[] = $condition;
        }

        return ' WHERE ' . implode(' ', $conditions);
    }

    private function buildGroupBy(): string
    {
        if (empty($this->groupBy)) {
            return '';
        }

        return ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    private function buildHaving(): string
    {
        if (empty($this->having)) {
            return '';
        }

        $conditions = [];
        foreach ($this->having as $having) {
            $conditions[] = "{$having['column']} {$having['operator']} ?";
            $this->parameters[] = $having['value'];
        }

        return ' HAVING ' . implode(' AND ', $conditions);
    }

    private function buildOrderBy(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        $orders = [];
        foreach ($this->orderBy as $order) {
            $orders[] = "{$order['column']} {$order['direction']}";
        }

        return ' ORDER BY ' . implode(', ', $orders);
    }

    private function buildLimit(): string
    {
        if ($this->limit === null) {
            return '';
        }

        $sql = " LIMIT {$this->limit}";

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }
}
