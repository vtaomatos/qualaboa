<?php
// api/chat.php
require_once '../variables.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

// Lê o input JSON
$input = json_decode(file_get_contents('php://input'), true);
$pergunta = trim($input['pergunta'] ?? '');
$data = trim($input['data'] ?? '');
$hora = trim($input['hora'] ?? '');
$eventos = $input['eventos'] ?? [];

if (!$pergunta) {
    echo json_encode(['erro' => 'Pergunta vazia']);
    exit;
}

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database.sqlite');

    $dataFiltro = $data ?? date('Y-m-d', strtotime('-5 hours'));
    $horaFiltro = $hora ?? '00:00:00';

    if (empty($eventos)) {
        // Preparar e executar query
        $sql = "SELECT * FROM eventos WHERE DATE(data_evento) = :data AND TIME(data_evento) >= :hora";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':data', $dataFiltro);
        $stmt->bindValue(':hora', $horaFiltro);
        $stmt->execute();
        
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo json_encode(['erro' => 'Erro ao conectar ao banco']);
    exit;
}

// Descrição simplificada para IA
$descricaoEventos = array_map(function ($e) {
    $flyerText = strip_tags($e['flyer_html']);
    return "{id:{$e['id']}, titulo:{$e['titulo']}, data:{$e['data_evento']}, descricao:{$e['descricao']}, endereco: {$e['endereco']}, flyer:{$flyerText}},";
}, $eventos);

$listaEventosTexto = implode("\n", $descricaoEventos);

// Prompt para IA
$mensagemSistema = <<<TXT
Receba essa lista de eventos e responda com um json de duas partes;
A primeira parte do json vai conter todos os ids dos eventos em ordem de relevância para as perguntas do usuário;
A segunda parte do json vai ser um texto html explicando suas considerações.
Ex: {"ordem": [1, 2, 3], "explicacao": "Humm vai rolar o evento tal que pode ser essa pegada. Ja o evento tal2 também até... "}
Se não houver eventos na lista, responda com a primeira parte obj vazio e segunda "Nada encontrado". E ou recomende buscar eventos em outra data.
Se o usuário fugir do assunto, lembre-o educadamente que você só pode ajudar a encontrar eventos.
Lista de eventos disponíveis:
$listaEventosTexto
Responda de forma amigável e com base no interesse do usuário como um agente de eventos.
TXT;

// Chamada para API da OpenAI (exemplo, ajuste sua API Key)
$apiKeyUse = $apiKey ?: '';

if (!$apiKeyUse) {
    echo json_encode(['erro' => 'API Key não configurada']);
    exit;
}

$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'system', 'content' => $mensagemSistema],
        ['role' => 'user', 'content' => $pergunta]
    ],
    'max_tokens' => 500,
    'temperature' => 0.8
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKeyUse,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
if(curl_errno($ch)) {
    echo json_encode(['erro' => 'Erro na requisição para OpenAI']);
    exit;
}
curl_close($ch);

$responseData = json_decode($response, true);
if (!isset($responseData['choices'][0]['message']['content'])) {
    echo json_encode(['erro' => 'Resposta inválida da OpenAI']);
    exit;
}

echo json_encode(['resposta' => $responseData['choices'][0]['message']['content']]);
