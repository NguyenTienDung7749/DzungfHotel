<?php
declare(strict_types=1);

function env_value(string $key): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return null;
    }

    return is_string($value) ? $value : (string) $value;
}

function is_local_db_fallback_allowed(): bool
{
    $appEnv = strtolower((string) env_value('APP_ENV'));

    if ($appEnv === 'local') {
        return true;
    }

    $candidates = [
        $_SERVER['HTTP_HOST'] ?? '',
        $_SERVER['SERVER_NAME'] ?? '',
        $_SERVER['SERVER_ADDR'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        $host = strtolower(trim((string) $candidate));

        if ($host === '') {
            continue;
        }

        $host = explode(':', $host, 2)[0];

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }
    }

    return false;
}

function db_config(): array
{
    $config = [
        'host' => env_value('DB_HOST'),
        'port' => env_value('DB_PORT'),
        'name' => env_value('DB_NAME'),
        'user' => env_value('DB_USER'),
        'password' => env_value('DB_PASSWORD'),
    ];

    if (is_local_db_fallback_allowed()) {
        $config['host'] = $config['host'] !== null && $config['host'] !== '' ? $config['host'] : '127.0.0.1';
        $config['port'] = $config['port'] !== null && $config['port'] !== '' ? $config['port'] : '3306';
        $config['name'] = $config['name'] !== null && $config['name'] !== '' ? $config['name'] : 'dzungfhotel';
        $config['user'] = $config['user'] !== null && $config['user'] !== '' ? $config['user'] : 'root';
        $config['password'] = $config['password'] ?? '';
    }

    $missing = [];

    foreach (['host' => 'DB_HOST', 'port' => 'DB_PORT', 'name' => 'DB_NAME', 'user' => 'DB_USER'] as $key => $envKey) {
        if ($config[$key] === null || trim((string) $config[$key]) === '') {
            $missing[] = $envKey;
        }
    }

    if ($config['password'] === null) {
        $missing[] = 'DB_PASSWORD';
    }

    if ($missing !== []) {
        throw new RuntimeException('Missing database environment variables: ' . implode(', ', $missing));
    }

    if (!ctype_digit((string) $config['port'])) {
        throw new RuntimeException('DB_PORT must be a valid integer.');
    }

    $config['port'] = (int) $config['port'];

    return $config;
}

function open_db_connection(): mysqli
{
    $config = db_config();
    $db = mysqli_init();

    if (!$db instanceof mysqli) {
        throw new RuntimeException('Unable to initialize MySQL client.');
    }

    $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

    $connected = @$db->real_connect(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['name'],
        $config['port']
    );

    if (!$connected) {
        $message = $db->connect_error !== '' ? $db->connect_error : 'Unknown MySQL connection error.';
        throw new RuntimeException($message);
    }

    if (!$db->set_charset('utf8mb4')) {
        throw new RuntimeException('Unable to set utf8mb4 charset.');
    }

    return $db;
}

function log_db_failure(Throwable $exception, string $context = 'Database error'): void
{
    error_log($context . ': ' . $exception->getMessage());
}

function db_failure_response(): never
{
    http_response_code(500);
    exit('Ung dung tam thoi khong the ket noi du lieu.');
}

function get_db(): mysqli
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    try {
        $db = open_db_connection();
        return $db;
    } catch (Throwable $exception) {
        log_db_failure($exception, 'Database connection failed');
        db_failure_response();
    }
}

function db_healthcheck(): array
{
    try {
        $db = open_db_connection();
        $isAlive = $db->ping();
        $db->close();

        return [
            'ok' => $isAlive,
            'message' => $isAlive ? 'OK' : 'Database ping failed',
        ];
    } catch (Throwable $exception) {
        log_db_failure($exception, 'Database health check failed');

        return [
            'ok' => false,
            'message' => 'Database unavailable',
        ];
    }
}
