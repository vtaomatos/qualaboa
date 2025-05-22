<?php
// api/chat.php

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Lê o input JSON
$input = json_decode(file_get_contents('php://input'), true);
$pergunta = trim($input['pergunta'] ?? '');

if (!$pergunta) {
    echo json_encode(['erro' => 'Pergunta vazia']);
    exit;
}

// Substitua pela sua chave da OpenAI
$apiKey = '';

// Endpoint da API
$url = 'https://api.openai.com/v1/chat/completions';

// Parâmetros do corpo da requisição
$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => 'Você é um guia de eventos para a cidade de Santos. Ajude as pessoas a encontrar festas, eventos e lugares interessantes.'],
        ['role' => 'user', 'content' => $pergunta]
    ],
    'max_tokens' => 150,
    'temperature' => 0.7
];

// Requisição via cURL
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: ' . "Bearer $apiKey"
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['erro' => 'Erro ao se comunicar com a IA: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Log para arquivo
file_put_contents(__DIR__ . '/log_resposta_openai.json', $response);

$resposta = json_decode($response, true);

// Se a estrutura esperada não existir
if (!isset($resposta['choices'][0]['message']['content'])) {
    echo json_encode([
        'erro' => 'Resposta inesperada da IA',
        'debug' => $resposta
    ]);
    exit;
}

// Resposta válida
$text = $resposta['choices'][0]['message']['content'];

// Retorna a resposta e o log no console do navegador
echo json_encode([
    'resposta' => $text,
    'debug' => [
        'prompt' => $data,
        'respostaBruta' => $resposta
    ]
]);
