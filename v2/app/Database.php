<?php

namespace App;

use PDO;
use PDOException;

class Database {

    public static function connect(): PDO
    {
        try {

            return new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

        } catch (PDOException $e) {

            die("Erro de conexão com banco.");
        }
    }
}

