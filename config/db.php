<?php
$host = "localhost";
$db = "qualaboa";
$user = "root";
$pass = "";

try {
    $conn = new PDO("sqlite:qualaboa.db");
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
