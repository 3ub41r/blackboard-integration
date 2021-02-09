<?php

namespace App\Services;

use PDO;

class SqlServerService
{
    public static function getConnection($serverName, $username, $password, $database)
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        return new PDO("sqlsrv:server=$serverName;Database=$database", $username, $password, $options);
    }
}