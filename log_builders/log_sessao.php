<?php
// api/log_sessao.php

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Lê JSON bruto
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    exit;
}

// Dados recebidos
$evento = $data['evento'] ?? 'heartbeat';
$tempo  = intval($data['tempo'] ?? 0);
$rota   = substr($data['rota'] ?? '/', 0, 255);

// Sanitização básica
$cidade = preg_replace('/[^a-zA-ZÀ-ú ]/', '', $data['cidade'] ?? 'Desconhecida');
$bairro = preg_replace('/[^a-zA-ZÀ-ú ]/', '', $data['bairro'] ?? 'Desconhecido');

// Infos do request
$ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
$hora = date('Y-m-d H:i:s');

// Pasta de logs
$dir = __DIR__ . '/../logs/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

// Arquivo diário
$arquivo = $dir . 'sessao_' . date('Y-m-d') . '.log';

// Formato padronizado (1 linha = 1 evento)
$linha = implode(' | ', [
    $hora,
    "EV:$evento",
    "IP:$ip",
    "Cidade:$cidade",
    "Bairro:$bairro",
    "Tempo:$tempo"."s",
    "Rota:$rota",
    "UA:$ua"
]) . "\n";

// Grava
file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);

// Resposta mínima
http_response_code(204);
