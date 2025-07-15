<?php
// api/chat.php
require_once '../secretsConstants.php';
require_once '../config/db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

$debug = isset($_GET['debug']) ? true : false;

function log_event($msg) {
    global $debug;
    $prefix = date('[Y-m-d H:i:s] ');
    if ($debug) {
        echo "<pre>$prefix$msg</pre>";
    }
    error_log($prefix . $msg);
}

// Lê o input JSON
$input = json_decode(file_get_contents('php://input'), true);
log_event("Entrada recebida: " . json_encode($input));

$pergunta = trim($input['pergunta'] ?? '');
$data = trim($input['data'] ?? '');
$hora = trim($input['hora'] ?? '');
$eventos = $input['eventos'] ?? [];

if (!$pergunta) {
    log_event("Pergunta vazia.");
    echo json_encode(['erro' => 'Pergunta vazia']);
    exit;
}

try {
    $dataFiltro = $data ?: date('Y-m-d', strtotime('-5 hours'));
    $horaFiltro = $hora ?: '00:00:00';

    log_event("Data filtro: $dataFiltro | Hora filtro: $horaFiltro");

    if (empty($eventos)) {
        $sql = "SELECT * FROM eventos WHERE DATE(data_evento) = :data AND TIME(data_evento) >= :hora";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':data', $dataFiltro);
        $stmt->bindValue(':hora', $horaFiltro);
        $stmt->execute();

        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        log_event("Eventos carregados do banco: " . count($eventos));
    } else {
        log_event("Eventos recebidos por parâmetro: " . count($eventos));
    }
} catch (PDOException $e) {
    log_event("Erro PDO: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao conectar ao banco']);
    exit;
}

// Monta descrição dos eventos
$descricaoEventos = array_map(function ($e) {
    $flyerText = strip_tags($e['flyer'] ?? '');
    return "{id:{$e['id']}, titulo:{$e['titulo']}, data:{$e['data_evento']}, descricao:{$e['descricao']}, endereco: {$e['endereco']}, flyer:{$flyerText}}";
}, $eventos);

$listaEventosTexto = implode("\n", $descricaoEventos);

log_event("Lista formatada de eventos para IA:\n" . $listaEventosTexto);

// Prompt da IA
$mensagemSistema = <<<TXT
Receba essa lista de eventos e responda com um json de duas partes;
A primeira parte do json vai conter todos os ids dos eventos em ordem de relevância para as perguntas do usuário;
A segunda parte do json vai ser um texto html explicando suas considerações.
Ex: {"ordem": [1, 2, 3], "explicacao": "Humm vai rolar o evento tal que pode ser essa pegada..."}
Se não houver eventos na lista, responda com a primeira parte obj vazio e segunda "Nada encontrado".
Se o usuário fugir do assunto, lembre-o educadamente que você só pode ajudar a encontrar eventos.
Lista de eventos disponíveis:
$listaEventosTexto
Responda de forma amigável e com base no interesse do usuário como um agente de eventos.
Responda tudo dentro de um unico json válido.
TXT;

$apiKeyUse = APIKEY ?: '';
if (!$apiKeyUse) {
    log_event("API Key não configurada");
    echo json_encode(['erro' => 'API Key não configurada']);
    exit;
}

// Monta payload da requisição
$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => $mensagemSistema],
        ['role' => 'user', 'content' => $pergunta]
    ],
    'max_tokens' => 500,
    'temperature' => 0.8
];

log_event("Payload enviado para OpenAI:\n" . json_encode($data));

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKeyUse,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);

if (curl_errno($ch)) {
    $curlError = curl_error($ch);
    log_event("Erro CURL: $curlError");
    echo json_encode(['erro' => 'Erro na requisição para OpenAI']);
    exit;
}
curl_close($ch);

$responseData = json_decode($response, true);
log_event("Resposta da OpenAI:\n" . $response);

// Verifica se houve erro
if (isset($responseData['error'])) {
    $errorCode = $responseData['error']['code'] ?? 'erro_desconhecido';
    $errorMessage = $responseData['error']['message'] ?? 'Erro desconhecido';

    log_event("Erro da OpenAI [{$errorCode}]: {$errorMessage}");

    if ($errorCode === 'insufficient_quota') {
        echo json_encode([
            'erro' => 'Sistema temporariamente indisponível, tente novamente em breve.',
            'codigo' => 'quota-excedida'
        ]);
    } else {
        echo json_encode([
            'erro' => 'Erro ao processar a solicitação.',
            'codigo' => $errorCode
        ]);
    }
    exit;
}

// Caso resposta válida
if (!isset($responseData['choices'][0]['message']['content'])) {
    log_event("Resposta inválida da OpenAI");
    echo json_encode([
        'erro' => 'Erro ao processar a resposta da IA.',
        'codigo' => 'resposta-invalida'
    ]);
    exit;
}


echo json_encode(['resposta' => $responseData['choices'][0]['message']['content']]);
