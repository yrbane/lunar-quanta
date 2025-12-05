<?php

declare(strict_types=1);

namespace Tests\Service\Database\Migration;

use Lunar\Service\Database\Migration\Migration;
use Lunar\Service\Database\Migration\MigrationRunner;
use Lunar\Service\Database\Migration\Schema;
use Lunar\Service\Database\Migration\Blueprint;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le système de migrations de base de données.
 *
 * Les migrations permettent de versionner le schéma de la base de données.
 * Chaque migration définit des méthodes up() et down() pour appliquer
 * et annuler les changements.
 */
final class MigrationTest extends TestCase
{
    // =========================================================================
    // Tests du Schema Builder
    // =========================================================================

    public function testCreateTable(): void
    {
        $sql = Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('created_at');
        });

        $this->assertStringContainsString('CREATE TABLE', $sql);
        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('id', $sql);
        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('email', $sql);
        $this->assertStringContainsString('UNIQUE', $sql);
    }

    public function testDropTable(): void
    {
        $sql = Schema::drop('users');

        $this->assertSame('DROP TABLE IF EXISTS users', $sql);
    }

    public function testDropTableIfExists(): void
    {
        $sql = Schema::dropIfExists('users');

        $this->assertSame('DROP TABLE IF EXISTS users', $sql);
    }

    public function testRenameTable(): void
    {
        $sql = Schema::rename('users', 'members');

        $this->assertSame('ALTER TABLE users RENAME TO members', $sql);
    }

    // =========================================================================
    // Tests du Blueprint (définition de colonnes)
    // =========================================================================

    public function testBlueprintId(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('id', $sql);
        $this->assertStringContainsString('PRIMARY KEY', $sql);
        $this->assertStringContainsString('AUTOINCREMENT', $sql);
    }

    public function testBlueprintUuid(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('id')->primary();

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('VARCHAR(36)', $sql);
        $this->assertStringContainsString('PRIMARY KEY', $sql);
    }

    public function testBlueprintString(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('name', 100);

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('VARCHAR(100)', $sql);
    }

    public function testBlueprintText(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->text('content');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('content', $sql);
        $this->assertStringContainsString('TEXT', $sql);
    }

    public function testBlueprintInteger(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('age');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('age', $sql);
        $this->assertStringContainsString('INTEGER', $sql);
    }

    public function testBlueprintBoolean(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('is_active')->default(true);

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('is_active', $sql);
        $this->assertStringContainsString('BOOLEAN', $sql);
        $this->assertStringContainsString('DEFAULT', $sql);
    }

    public function testBlueprintTimestamp(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('TIMESTAMP', $sql);
    }

    public function testBlueprintTimestamps(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('updated_at', $sql);
    }

    public function testBlueprintNullable(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('bio')->nullable();

        $sql = $blueprint->toSql();
        $this->assertStringNotContainsString('NOT NULL', $sql);
    }

    public function testBlueprintNotNullByDefault(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('name');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('NOT NULL', $sql);
    }

    public function testBlueprintDefault(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('status')->default('pending');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString("DEFAULT 'pending'", $sql);
    }

    public function testBlueprintUnique(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('email')->unique();

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('UNIQUE', $sql);
    }

    public function testBlueprintIndex(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('email');
        $blueprint->index('email');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('CREATE INDEX', $sql);
    }

    public function testBlueprintForeignKey(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->integer('user_id');
        $blueprint->foreign('user_id')->references('id')->on('users');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('FOREIGN KEY', $sql);
        $this->assertStringContainsString('REFERENCES', $sql);
        $this->assertStringContainsString('users', $sql);
    }

    public function testBlueprintForeignKeyOnDelete(): void
    {
        $blueprint = new Blueprint('posts');
        $blueprint->integer('user_id');
        $blueprint->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('CASCADE');

        $sql = $blueprint->toSql();
        $this->assertStringContainsString('ON DELETE CASCADE', $sql);
    }

    // =========================================================================
    // Tests de la classe Migration abstraite
    // =========================================================================

    public function testMigrationHasUpAndDownMethods(): void
    {
        $migration = new class extends Migration {
            public function up(): void
            {
                Schema::create('test', function (Blueprint $table) {
                    $table->id();
                });
            }

            public function down(): void
            {
                Schema::drop('test');
            }
        };

        // Vérifier que les méthodes existent
        $this->assertTrue(method_exists($migration, 'up'));
        $this->assertTrue(method_exists($migration, 'down'));
    }

    // =========================================================================
    // Tests du MigrationRunner
    // =========================================================================

    public function testMigrationRunnerGetPendingMigrations(): void
    {
        $migrationsPath = sys_get_temp_dir() . '/lunar_migrations_' . uniqid();
        mkdir($migrationsPath, 0755, true);

        // Créer des fichiers de migration de test
        file_put_contents(
            $migrationsPath . '/2025_01_01_000000_create_users_table.php',
            '<?php return new class extends \Lunar\Service\Database\Migration\Migration {
                public function up(): void {}
                public function down(): void {}
            };'
        );

        $runner = new MigrationRunner($migrationsPath);
        $pending = $runner->getPendingMigrations([]);

        $this->assertCount(1, $pending);
        $this->assertStringContainsString('create_users_table', $pending[0]);

        // Cleanup
        unlink($migrationsPath . '/2025_01_01_000000_create_users_table.php');
        rmdir($migrationsPath);
    }

    public function testMigrationRunnerSortsByName(): void
    {
        $migrationsPath = sys_get_temp_dir() . '/lunar_migrations_' . uniqid();
        mkdir($migrationsPath, 0755, true);

        // Créer des fichiers dans le désordre
        file_put_contents(
            $migrationsPath . '/2025_01_02_000000_second.php',
            '<?php return new class extends \Lunar\Service\Database\Migration\Migration {
                public function up(): void {}
                public function down(): void {}
            };'
        );
        file_put_contents(
            $migrationsPath . '/2025_01_01_000000_first.php',
            '<?php return new class extends \Lunar\Service\Database\Migration\Migration {
                public function up(): void {}
                public function down(): void {}
            };'
        );

        $runner = new MigrationRunner($migrationsPath);
        $pending = $runner->getPendingMigrations([]);

        $this->assertCount(2, $pending);
        $this->assertStringContainsString('first', $pending[0]);
        $this->assertStringContainsString('second', $pending[1]);

        // Cleanup
        unlink($migrationsPath . '/2025_01_01_000000_first.php');
        unlink($migrationsPath . '/2025_01_02_000000_second.php');
        rmdir($migrationsPath);
    }

    public function testMigrationRunnerExcludesAlreadyRun(): void
    {
        $migrationsPath = sys_get_temp_dir() . '/lunar_migrations_' . uniqid();
        mkdir($migrationsPath, 0755, true);

        file_put_contents(
            $migrationsPath . '/2025_01_01_000000_create_users_table.php',
            '<?php return new class extends \Lunar\Service\Database\Migration\Migration {
                public function up(): void {}
                public function down(): void {}
            };'
        );
        file_put_contents(
            $migrationsPath . '/2025_01_02_000000_create_posts_table.php',
            '<?php return new class extends \Lunar\Service\Database\Migration\Migration {
                public function up(): void {}
                public function down(): void {}
            };'
        );

        $runner = new MigrationRunner($migrationsPath);
        $alreadyRun = ['2025_01_01_000000_create_users_table'];
        $pending = $runner->getPendingMigrations($alreadyRun);

        $this->assertCount(1, $pending);
        $this->assertStringContainsString('create_posts_table', $pending[0]);

        // Cleanup
        unlink($migrationsPath . '/2025_01_01_000000_create_users_table.php');
        unlink($migrationsPath . '/2025_01_02_000000_create_posts_table.php');
        rmdir($migrationsPath);
    }
}
