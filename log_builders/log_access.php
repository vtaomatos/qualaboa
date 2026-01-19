<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$headers = getallheaders();

$isPrefetch =
    (isset($headers['Purpose']) && $headers['Purpose'] === 'prefetch') ||
    (isset($headers['Sec-Purpose']) && $headers['Sec-Purpose'] === 'prefetch');

if ($isPrefetch) {
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || empty($data['sessao_id']) || empty($data['page'])) {
    http_response_code(400);
    exit;
}

$log_dir = __DIR__ . '/../logs/';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

$log_file = $log_dir . 'access_' . date('Y-m-d') . '.log';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$hora = date('Y-m-d H:i:s');

$sessao_id = preg_replace('/[^a-zA-Z0-9\-]/', '', $data['sessao_id']);
$page = substr($data['page'], 0, 300);

$linha = "$hora | SID:$sessao_id | IP:$ip | PAGE:$page | UA:$user_agent\n";

/**
 * 🔒 Trava simples: evita duplicar sessão+page
 */
if (file_exists($log_file)) {
    $conteudo = file_get_contents($log_file);
    if (str_contains($conteudo, "SID:$sessao_id | PAGE:$page")) {
        exit;
    }
}

file_put_contents($log_file, $linha, FILE_APPEND);
