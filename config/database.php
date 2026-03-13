<?php

declare(strict_types=1);

class Database
{
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;

    public function __construct()
    {
        $this->host = (string) (getenv('DB_HOST') ?: 'sql302.hstn.me');
        $this->dbName = (string) (getenv('DB_NAME') ?: 'mseet_41369034_plotter_reportes');
        $this->username = (string) (getenv('DB_USER') ?: 'mseet_41369034');
        $this->password = (string) (getenv('DB_PASS') ?: '4016508a8b');
        $this->charset = (string) (getenv('DB_CHARSET') ?: 'utf8mb4');
        
    }

    public function getConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $this->host,
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
}
