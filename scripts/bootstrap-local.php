#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envExamplePath = $root.DIRECTORY_SEPARATOR.'.env.example';
$envPath = $root.DIRECTORY_SEPARATOR.'.env';
$vendorAutoloadPath = $root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
$publicStoragePath = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'storage';
$sqlitePath = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';

main($argv);

function main(array $argv): void
{
    global $envExamplePath, $envPath, $vendorAutoloadPath, $publicStoragePath, $sqlitePath, $root;

    $options = parseArguments(array_slice($argv, 1));

    if (! file_exists($vendorAutoloadPath)) {
        fail("Composer dependencies are missing. Run 'composer install' first.");
    }

    if (! file_exists($envExamplePath)) {
        fail('Missing .env.example. Unable to prepare the environment file.');
    }

    if (! file_exists($envPath)) {
        if (! copy($envExamplePath, $envPath)) {
            fail('Unable to create .env from .env.example.');
        }

        out('Created .env from .env.example');
    } else {
        out('Using existing .env');
    }

    $envContents = (string) file_get_contents($envPath);
    $envContents = setEnvValue($envContents, 'APP_ENV', 'local');
    $envContents = setEnvValue($envContents, 'APP_DEBUG', 'true');
    $envContents = setEnvValue($envContents, 'APP_URL', $options['app-url']);
    $envContents = setEnvValue($envContents, 'MAIL_MAILER', 'log');

    if ($options['driver'] === 'sqlite') {
        ensureSqliteDatabase($sqlitePath);

        $envContents = setEnvValue($envContents, 'DB_CONNECTION', 'sqlite');
        $envContents = setEnvValue($envContents, 'DB_DATABASE', normalizePath($sqlitePath));

        out('Configured local SQLite database');
    } else {
        $envContents = setEnvValue($envContents, 'DB_CONNECTION', 'mysql');
        $envContents = setEnvValue($envContents, 'DB_HOST', $options['db-host']);
        $envContents = setEnvValue($envContents, 'DB_PORT', $options['db-port']);
        $envContents = setEnvValue($envContents, 'DB_DATABASE', $options['db-database']);
        $envContents = setEnvValue($envContents, 'DB_USERNAME', $options['db-username']);
        $envContents = setEnvValue($envContents, 'DB_PASSWORD', $options['db-password']);

        out('Configured local MySQL database');
    }

    if (file_put_contents($envPath, $envContents) === false) {
        fail('Unable to update .env.');
    }

    runCommand([PHP_BINARY, 'artisan', 'key:generate', '--force'], $root);

    if (! file_exists($publicStoragePath)) {
        runCommand([PHP_BINARY, 'artisan', 'storage:link'], $root);
    } else {
        out('Storage link already exists');
    }

    runCommand([PHP_BINARY, 'artisan', 'migrate', '--graceful', '--force'], $root);

    echo PHP_EOL;
    echo 'AssetFlow is ready for local use.'.PHP_EOL;
    echo 'Next steps:'.PHP_EOL;
    echo '  1. Start the app with: php artisan serve'.PHP_EOL;
    echo '  2. Open: '.$options['app-url'].'/setup'.PHP_EOL;
    echo '  3. Complete first-run setup in the browser'.PHP_EOL;
}

/**
 * @return array{
 *     app-url: string,
 *     driver: string,
 *     db-host: string,
 *     db-port: string,
 *     db-database: string,
 *     db-username: string,
 *     db-password: string
 * }
 */
function parseArguments(array $arguments): array
{
    $options = [
        'app-url' => 'http://127.0.0.1:8000',
        'driver' => 'sqlite',
        'db-host' => '127.0.0.1',
        'db-port' => '3306',
        'db-database' => '',
        'db-username' => 'root',
        'db-password' => '',
    ];

    foreach ($arguments as $argument) {
        if ($argument === '-h' || $argument === '--help') {
            usage();
            exit(0);
        }

        if (! str_starts_with($argument, '--')) {
            fail("Unknown argument: {$argument}");
        }

        [$name, $value] = array_pad(explode('=', substr($argument, 2), 2), 2, null);

        if ($value === null) {
            fail("Missing value for --{$name}. Use --{$name}=value.");
        }

        if (! array_key_exists($name, $options)) {
            fail("Unknown option: --{$name}");
        }

        $options[$name] = $value;
    }

    if (! in_array($options['driver'], ['sqlite', 'mysql'], true)) {
        fail("Unsupported driver '{$options['driver']}'. Use sqlite or mysql.");
    }

    if ($options['driver'] === 'mysql' && trim($options['db-database']) === '') {
        fail('MySQL setup requires --db-database.');
    }

    return $options;
}

function usage(): void
{
    echo <<<'TEXT'
Usage:
  php scripts/bootstrap-local.php [--app-url=http://127.0.0.1:8000]
  php scripts/bootstrap-local.php --driver=mysql --db-database=assetflow [--db-username=root] [--db-password=secret]

Defaults:
  --driver=sqlite
  --app-url=http://127.0.0.1:8000
  --db-host=127.0.0.1
  --db-port=3306
  --db-username=root
  --db-password=

This script prepares .env, generates APP_KEY, links storage, and runs migrations.
TEXT;
    echo PHP_EOL;
}

function ensureSqliteDatabase(string $sqlitePath): void
{
    $directory = dirname($sqlitePath);

    if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        fail('Unable to create the database directory.');
    }

    if (! file_exists($sqlitePath) && file_put_contents($sqlitePath, '') === false) {
        fail('Unable to create database/database.sqlite.');
    }
}

function setEnvValue(string $contents, string $key, string $value): string
{
    $formattedValue = formatEnvValue($value);
    $pattern = '/^'.preg_quote($key, '/').'=.*/m';
    $line = "{$key}={$formattedValue}";

    if (preg_match($pattern, $contents) === 1) {
        return (string) preg_replace($pattern, $line, $contents, 1);
    }

    return rtrim($contents).PHP_EOL.$line.PHP_EOL;
}

function formatEnvValue(string $value): string
{
    if ($value === '') {
        return '';
    }

    if (preg_match('/^[A-Za-z0-9._:\/+-]+$/', $value) === 1) {
        return $value;
    }

    $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

    return "\"{$escaped}\"";
}

function normalizePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

/**
 * @param  list<string>  $command
 */
function runCommand(array $command, string $workingDirectory): void
{
    $display = implode(' ', array_map('displayCommandPart', $command));
    out("Running {$display}");

    $process = proc_open(
        $command,
        [
            0 => STDIN,
            1 => STDOUT,
            2 => STDERR,
        ],
        $pipes,
        $workingDirectory,
    );

    if (! is_resource($process)) {
        fail("Unable to start command: {$display}");
    }

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        fail("Command failed with exit code {$exitCode}: {$display}");
    }
}

function displayCommandPart(string $part): string
{
    return preg_match('/\s/', $part) === 1 ? "\"{$part}\"" : $part;
}

function out(string $message): void
{
    echo "[assetflow] {$message}".PHP_EOL;
}

function fail(string $message): never
{
    fwrite(STDERR, "[assetflow] {$message}".PHP_EOL);
    exit(1);
}
