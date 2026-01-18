<?php
// log_builders/log_access.php

$log_dir = __DIR__ . '/../logs/';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

$log_file = $log_dir . 'access_' . date('Y-m-d') . '.log';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rota = $_SERVER['REQUEST_URI'] ?? '/';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$hora = date('Y-m-d H:i:s');

$linha = "$hora | IP:$ip | Rota:$rota | UA:$user_agent\n";

file_put_contents($log_file, $linha, FILE_APPEND);
