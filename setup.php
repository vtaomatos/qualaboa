<?php
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    $pdo->exec($sql);
    echo "Banco de dados criado com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
