<?php
// api/chat.php

ini_set('display_errors', value: 1);
error_reporting(E_ALL);

// Lê o input JSON
$input = json_decode(file_get_contents('php://input'), true);
$pergunta = trim($input['pergunta'] ?? '');

if (!$pergunta) {
    echo json_encode(['erro' => 'Pergunta vazia']);
    exit;
}

// Conexão com banco
$conn = new mysqli("localhost", "root", "", "qualaboa");
if ($conn->connect_error) {
    echo json_encode(['erro' => 'Erro ao conectar ao banco']);
    exit;
} 


$dataFiltro = date('Y-m-d', strtotime('-5 hours'));

$dataFiltroEscaped = $conn->real_escape_string($dataFiltro);

$queryDebug = "SELECT id, titulo, data_evento, flyer_html 
               FROM eventos 
               WHERE DATE(data_evento) = '$dataFiltroEscaped' 
                AND TIME(data_evento) >= '00:00:00'
                ORDER BY data_evento ASC";

error_log("QUERY DEBUG:\n" . $queryDebug);

$result = $conn->query($queryDebug);

$eventos = [];
while ($row = $result->fetch_assoc()) {
    $eventos[] = $row;
}
$conn->close();

// Descrição simplificada para IA
$descricaoEventos = array_map(function ($e) {
    $flyerText = strip_tags($e['flyer_html']);
    return <<<DESC
{id:{$e['id']}, titulo:{$e['titulo']}, data:{$e['data_evento']}, flyer:{$flyerText}},
DESC;
}, $eventos);

$listaEventosTexto = implode("\n", $descricaoEventos);

// Prompt
$mensagemSistema = <<<TXT
Receba essa lista de eventos e responda com um json de duas partes;
A primeira parte do json vai conter todos os ids dos eventos em ordem de relevância para as perguntas do usuário;
A segunga parte do json vai ser um texto html explicando suas considerações.
Ex: {"ordem": [1, 2, 3], "explicacao": "Humm vai rolar o evento tal que pode ser essa pegada. Ja o eventos tal2 também até... "}
Se não houver eventos na lista, responda com a primeira parte obj vazio e segunda "Nada encontrado". E ou recomende buscar eventos em outra data.
Se o usuário fugir do assunto, lembre-o educadamente que você só pode ajudar a encontrar eventos.
Lista de eventos disponíveis:
$listaEventosTexto
Responda de forma amigável e com base no interesse do usuário como um agente de eventos.
TXT;

// API Key
$apiKey = '';

$url = 'https://api.openai.com/v1/chat/completions';
$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => $mensagemSistema],
        ['role' => 'user', 'content' => $pergunta]
    ],
    'max_tokens' => 500,
    'temperature' => 0.8
];

if (!empty($listaEventosTexto)) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . 'Bearer ' . $apiKey
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo json_encode(['erro' => 'Erro ao se comunicar com a IA: ' . curl_error($ch)]);
        file_put_contents('log_openai_response.txt', $response);
        curl_close($ch);
        exit;
    }

    curl_close($ch);
    $resposta = json_decode($response, true);


    if (!isset($resposta['choices'][0]['message']['content'])) {
        echo json_encode(['erro' => 'Resposta inesperada da IA', 'debug' => $resposta]);
        exit;
    }

    $retorno = json_encode([
        'resposta' => $resposta['choices'][0]['message']['content'],
        'debug' => [
            'prompt' => $data,
            'eventos_enviados' => $descricaoEventos,
            'respostaBruta' => $resposta
        ]
    ]);

    error_log("Resposta da IA:\n" . $retorno);
    echo $retorno;

} else {
    echo json_encode(['resposta' => 'Nenhum evento disponível para hoje.']);
}
