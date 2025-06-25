<?php
// Requisitos: PHP 8+, curl habilitado, chave da API OpenAI em .env ou configurada abaixo

// 1. Configuração da chave da API
require_once '../variables.php';

$apiKey = $apiKey ?: 'SUA_CHAVE_AQUI';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagem'])) {
    $tmpPath = $_FILES['imagem']['tmp_name'];
    $fileName = basename($_FILES['imagem']['name']);

    // Validação de imagem
    $mime = mime_content_type($tmpPath);
    if (!str_starts_with($mime, 'image/')) {
        http_response_code(400);
        echo "Arquivo enviado não é uma imagem.";
        exit;
    }

    // Lê a imagem como base64
    $imageData = base64_encode(file_get_contents($tmpPath));

    // Prompt sucinto
    $prompt = "Leia a imagem enviada e extraia todos os textos visíveis de forma limpa. Com base nesses textos, determine se a imagem é um flyer com descrição de evento. Se for, gere um comando SQL no seguinte formato: INSERT INTO eventos (titulo, data_evento, tipo_conteudo, flyer_html, flyer_imagem, latitude, longitude, descricao, endereco) VALUES (...). Corrija erros de OCR se forem evidentes. Se não for um evento, diga apenas: 'Imagem não corresponde a evento'.";

    // 2. Monta a requisição para a API OpenAI (GPT-4o com imagem)
    $payload = json_encode([
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'user', 'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => [
                    'url' => 'data:' . $mime . ';base64,' . $imageData
                ]],
            ]]
        ],
        'max_tokens' => 1000,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $resposta = $data['choices'][0]['message']['content'] ?? 'Erro ao interpretar a resposta.';

    if (str_starts_with($resposta, 'INSERT INTO eventos')) {
        // Aqui você pode usar PDO para executar o SQL (após validação, claro)
        // Exemplo de execução segura:
        // try {
        //     $pdo = new PDO('sqlite:./eventos.sqlite');
        //     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //     $pdo->exec($resposta); // Valida se quiser antes

        //     echo "Evento inserido com sucesso.";
        // } catch (PDOException $e) {
        //     echo "Erro ao inserir no banco: " . $e->getMessage();
    }
    echo nl2br(htmlspecialchars($resposta));
} else {
    // Formulário simples
    echo '<form method="POST" enctype="multipart/form-data">
        <label>Envie uma imagem:</label><br>
        <input type="file" name="imagem" accept="image/*" required><br><br>
        <button type="submit">Analisar e Inserir</button>
    </form>';
}
?>
