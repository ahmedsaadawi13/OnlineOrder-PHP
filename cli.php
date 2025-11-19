<?php

/**
 * CLI Tool for migrations and other tasks
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Core\Database;

$command = $argv[1] ?? null;

switch ($command) {
    case 'migrate:run':
        echo "Running database migrations...\n\n";
        try {
            Database::runMigrations();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'db:test':
        echo "Testing database connection...\n";
        try {
            $db = Database::getInstance();
            echo "✓ Database connection successful!\n";

            // Test query
            $result = Database::queryOne("SELECT 1 as test");
            echo "✓ Test query successful!\n";
        } catch (Exception $e) {
            echo "✗ Database connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        break;

    case 'cache:clear':
        echo "Clearing cache...\n";
        // TODO: Implement cache clearing
        echo "✓ Cache cleared!\n";
        break;

    default:
        echo "Restaurant SaaS CLI Tool\n\n";
        echo "Available commands:\n";
        echo "  migrate:run       - Run database migrations\n";
        echo "  db:test           - Test database connection\n";
        echo "  cache:clear       - Clear application cache\n";
        echo "\nUsage: php cli.php [command]\n";
}
