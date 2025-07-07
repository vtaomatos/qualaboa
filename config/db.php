<?php
require_once __DIR__ . '/../secretsConstants.php';

$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>
