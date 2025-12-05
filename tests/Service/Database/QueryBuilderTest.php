<?php

declare(strict_types=1);

namespace Tests\Service\Database;

use Lunar\Service\Database\QueryBuilder;
use Lunar\Service\Database\Connection;
use Lunar\Service\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le Query Builder.
 *
 * Le Query Builder permet de construire des requêtes SQL de manière
 * programmatique et sécurisée (protection contre les injections SQL
 * via les paramètres préparés).
 *
 * Pattern utilisé : Fluent Interface (méthodes chainées)
 */
final class QueryBuilderTest extends TestCase
{
    // =========================================================================
    // Tests SELECT
    // =========================================================================

    public function testSelectAllFromTable(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('*')
            ->from('users')
            ->toSql();

        $this->assertSame('SELECT * FROM users', $sql);
    }

    public function testSelectSpecificColumns(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('id', 'name', 'email')
            ->from('users')
            ->toSql();

        $this->assertSame('SELECT id, name, email FROM users', $sql);
    }

    public function testSelectWithAlias(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('u.id', 'u.name AS username')
            ->from('users', 'u')
            ->toSql();

        $this->assertSame('SELECT u.id, u.name AS username FROM users AS u', $sql);
    }

    // =========================================================================
    // Tests WHERE
    // =========================================================================

    public function testWhereEqual(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->where('id', '=', 1);

        $this->assertSame('SELECT * FROM users WHERE id = ?', $qb->toSql());
        $this->assertSame([1], $qb->getParameters());
    }

    public function testWhereWithMultipleConditions(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->where('status', '=', 'active')
            ->where('role', '=', 'admin');

        $this->assertSame(
            'SELECT * FROM users WHERE status = ? AND role = ?',
            $qb->toSql()
        );
        $this->assertSame(['active', 'admin'], $qb->getParameters());
    }

    public function testWhereOr(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->where('role', '=', 'admin')
            ->orWhere('role', '=', 'moderator');

        $this->assertSame(
            'SELECT * FROM users WHERE role = ? OR role = ?',
            $qb->toSql()
        );
    }

    public function testWhereIn(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->whereIn('id', [1, 2, 3]);

        $this->assertSame('SELECT * FROM users WHERE id IN (?, ?, ?)', $qb->toSql());
        $this->assertSame([1, 2, 3], $qb->getParameters());
    }

    public function testWhereNotIn(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->whereNotIn('status', ['banned', 'deleted']);

        $this->assertSame('SELECT * FROM users WHERE status NOT IN (?, ?)', $qb->toSql());
    }

    public function testWhereNull(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->whereNull('deleted_at');

        $this->assertSame('SELECT * FROM users WHERE deleted_at IS NULL', $qb->toSql());
    }

    public function testWhereNotNull(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->whereNotNull('email_verified_at');

        $this->assertSame('SELECT * FROM users WHERE email_verified_at IS NOT NULL', $qb->toSql());
    }

    public function testWhereBetween(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('orders')
            ->whereBetween('created_at', '2025-01-01', '2025-12-31');

        $this->assertSame(
            'SELECT * FROM orders WHERE created_at BETWEEN ? AND ?',
            $qb->toSql()
        );
        $this->assertSame(['2025-01-01', '2025-12-31'], $qb->getParameters());
    }

    public function testWhereLike(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->whereLike('name', '%John%');

        $this->assertSame('SELECT * FROM users WHERE name LIKE ?', $qb->toSql());
        $this->assertSame(['%John%'], $qb->getParameters());
    }

    // =========================================================================
    // Tests ORDER BY
    // =========================================================================

    public function testOrderByAsc(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('*')
            ->from('users')
            ->orderBy('name')
            ->toSql();

        $this->assertSame('SELECT * FROM users ORDER BY name ASC', $sql);
    }

    public function testOrderByDesc(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('*')
            ->from('users')
            ->orderBy('created_at', 'DESC')
            ->toSql();

        $this->assertSame('SELECT * FROM users ORDER BY created_at DESC', $sql);
    }

    public function testMultipleOrderBy(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('*')
            ->from('users')
            ->orderBy('role')
            ->orderBy('name', 'DESC')
            ->toSql();

        $this->assertSame('SELECT * FROM users ORDER BY role ASC, name DESC', $sql);
    }

    // =========================================================================
    // Tests LIMIT / OFFSET
    // =========================================================================

    public function testLimit(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('*')
            ->from('users')
            ->limit(10)
            ->toSql();

        $this->assertSame('SELECT * FROM users LIMIT 10', $sql);
    }

    public function testLimitWithOffset(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('*')
            ->from('users')
            ->limit(10)
            ->offset(20)
            ->toSql();

        $this->assertSame('SELECT * FROM users LIMIT 10 OFFSET 20', $sql);
    }

    // =========================================================================
    // Tests JOIN
    // =========================================================================

    public function testInnerJoin(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('u.name', 'p.title')
            ->from('users', 'u')
            ->join('posts', 'p', 'p.user_id = u.id')
            ->toSql();

        $this->assertSame(
            'SELECT u.name, p.title FROM users AS u INNER JOIN posts AS p ON p.user_id = u.id',
            $sql
        );
    }

    public function testLeftJoin(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('u.name', 'p.title')
            ->from('users', 'u')
            ->leftJoin('posts', 'p', 'p.user_id = u.id')
            ->toSql();

        $this->assertSame(
            'SELECT u.name, p.title FROM users AS u LEFT JOIN posts AS p ON p.user_id = u.id',
            $sql
        );
    }

    // =========================================================================
    // Tests GROUP BY / HAVING
    // =========================================================================

    public function testGroupBy(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('role', 'COUNT(*) as count')
            ->from('users')
            ->groupBy('role')
            ->toSql();

        $this->assertSame('SELECT role, COUNT(*) as count FROM users GROUP BY role', $sql);
    }

    public function testHaving(): void
    {
        $qb = new QueryBuilder();

        $qb->select('role', 'COUNT(*) as count')
            ->from('users')
            ->groupBy('role')
            ->having('COUNT(*)', '>', 5);

        $this->assertSame(
            'SELECT role, COUNT(*) as count FROM users GROUP BY role HAVING COUNT(*) > ?',
            $qb->toSql()
        );
        $this->assertSame([5], $qb->getParameters());
    }

    // =========================================================================
    // Tests INSERT
    // =========================================================================

    public function testInsert(): void
    {
        $qb = new QueryBuilder();

        $qb->insert('users')
            ->values([
                'name' => 'John',
                'email' => 'john@example.com'
            ]);

        $this->assertSame(
            'INSERT INTO users (name, email) VALUES (?, ?)',
            $qb->toSql()
        );
        $this->assertSame(['John', 'john@example.com'], $qb->getParameters());
    }

    // =========================================================================
    // Tests UPDATE
    // =========================================================================

    public function testUpdate(): void
    {
        $qb = new QueryBuilder();

        $qb->update('users')
            ->set(['name' => 'Jane', 'status' => 'active'])
            ->where('id', '=', 1);

        $this->assertSame(
            'UPDATE users SET name = ?, status = ? WHERE id = ?',
            $qb->toSql()
        );
        $this->assertSame(['Jane', 'active', 1], $qb->getParameters());
    }

    // =========================================================================
    // Tests DELETE
    // =========================================================================

    public function testDelete(): void
    {
        $qb = new QueryBuilder();

        $qb->delete('users')
            ->where('id', '=', 1);

        $this->assertSame('DELETE FROM users WHERE id = ?', $qb->toSql());
        $this->assertSame([1], $qb->getParameters());
    }

    // =========================================================================
    // Tests COUNT / Aggregates
    // =========================================================================

    public function testCount(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select('COUNT(*)')
            ->from('users')
            ->where('status', '=', 'active')
            ->toSql();

        $this->assertSame('SELECT COUNT(*) FROM users WHERE status = ?', $sql);
    }

    // =========================================================================
    // Tests Raw expressions
    // =========================================================================

    public function testSelectRaw(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->selectRaw('COUNT(*) as total, AVG(age) as avg_age')
            ->from('users')
            ->toSql();

        $this->assertSame('SELECT COUNT(*) as total, AVG(age) as avg_age FROM users', $sql);
    }

    public function testWhereRaw(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->whereRaw('YEAR(created_at) = ?', [2025]);

        $this->assertSame('SELECT * FROM users WHERE YEAR(created_at) = ?', $qb->toSql());
        $this->assertSame([2025], $qb->getParameters());
    }

    // =========================================================================
    // Tests de sécurité
    // =========================================================================

    public function testParametersAreProperlyEscaped(): void
    {
        $qb = new QueryBuilder();

        $qb->select('*')
            ->from('users')
            ->where('name', '=', "Robert'; DROP TABLE users;--");

        // Les paramètres sont séparés de la requête SQL
        $this->assertSame('SELECT * FROM users WHERE name = ?', $qb->toSql());
        $this->assertSame(["Robert'; DROP TABLE users;--"], $qb->getParameters());
    }

    // =========================================================================
    // Tests de clonage (pour réutiliser une base de requête)
    // =========================================================================

    public function testClone(): void
    {
        $base = new QueryBuilder();
        $base->select('*')->from('users')->where('status', '=', 'active');

        $qb1 = clone $base;
        $qb1->where('role', '=', 'admin');

        $qb2 = clone $base;
        $qb2->where('role', '=', 'user');

        // Les valeurs sont dans les paramètres, pas dans le SQL
        $qb1->toSql();
        $qb2->toSql();

        $this->assertContains('admin', $qb1->getParameters());
        $this->assertContains('user', $qb2->getParameters());
        $this->assertNotContains('admin', $qb2->getParameters());
    }
}
