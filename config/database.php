<?php

declare(strict_types=1);

class Database
{
    private string $host;
    private string $port;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;

    public function __construct()
    {
        $defaults = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbName' => 'plotter_reportes',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ];

        $urlConfig = $this->parseDatabaseUrl((string) (getenv('DATABASE_URL') ?: ''));

        $this->host = (string) (getenv('DB_HOST') ?: ($urlConfig['host'] ?? $defaults['host']));
        $this->port = (string) (getenv('DB_PORT') ?: ($urlConfig['port'] ?? $defaults['port']));
        $this->dbName = (string) (getenv('DB_NAME') ?: ($urlConfig['dbName'] ?? $defaults['dbName']));
        $this->username = (string) (getenv('DB_USER') ?: ($urlConfig['username'] ?? $defaults['username']));
        $this->password = (string) (getenv('DB_PASS') ?: ($urlConfig['password'] ?? $defaults['password']));
        $this->charset = (string) (getenv('DB_CHARSET') ?: ($urlConfig['charset'] ?? $defaults['charset']));
    }

    public function getConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->dbName,
            $this->charset
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            throw new RuntimeException('No fue posible conectar con la base de datos.', 0, $exception);
        }
    }

    private function parseDatabaseUrl(string $databaseUrl): array
    {
        if ($databaseUrl === '') {
            return [];
        }

        $parts = parse_url($databaseUrl);
        if ($parts === false || !isset($parts['scheme']) || $parts['scheme'] !== 'mysql') {
            return [];
        }

        $path = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
        if ($path === '') {
            return [];
        }

        $config = [
            'host' => (string) ($parts['host'] ?? ''),
            'port' => (string) ($parts['port'] ?? '3306'),
            'dbName' => $path,
            'username' => (string) ($parts['user'] ?? ''),
            'password' => (string) ($parts['pass'] ?? ''),
            'charset' => 'utf8mb4',
        ];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            if (!empty($queryParams['charset']) && is_string($queryParams['charset'])) {
                $config['charset'] = $queryParams['charset'];
            }
        }

        return $config;
    }
}
