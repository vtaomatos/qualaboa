<?php
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('America/Sao_Paulo');

if (isset($_GET['sessao_id']) && preg_match('/^[a-zA-Z0-9]+$/', $_GET['sessao_id'])) {
    session_id($_GET['sessao_id']);
}

session_start();

// ==========================
// ENVIO DE FEEDBACK    
// ==========================
$feedback_enviado = false;
$feedback_erro = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessao_id = session_id();
    $tipo = $_POST['tipo'] ?? '';
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($tipo && $mensagem) {
        try {
            $stmt = $conn->prepare("INSERT INTO feedbacks (sessao_id, tipo, mensagem, criado_em) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sessao_id, $tipo, $mensagem, date('Y-m-d H:i:s')]);
            $feedback_enviado = true;
        } catch (PDOException $e) {
            $feedback_erro = "Erro ao enviar feedback: " . $e->getMessage();
        }
    } else {
        $feedback_erro = "Por favor, preencha todos os campos antes de enviar.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual e Feedback - Qual a Boa?!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        h1,
        h2 {
            text-align: center;
            color: #209CEE;
        }

        form {
            margin-top: 1rem;
        }

        textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #209CEE;
            border-radius: 6px;
            resize: vertical;
        }

        button {
            padding: 0.5rem 1rem;
            background: #209CEE;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .alert {
            margin-top: 1rem;
        }

        a {
            color: #209CEE;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="legenda-container">
        <h1>Qual a Boa?! - Manual do Usuário</h1>

        <p>Este site tem como objetivo <strong>informar e orientar sobre eventos na sua região</strong>, ajudando
            moradores e turistas a decidirem sua programação diária.</p>

        <h2>Como o sistema funciona</h2>
        <ul>
            <li>Atualmente você pode buscar eventos por <strong>uma única data</strong> de cada vez.</li>
            <li>O sistema ainda não funciona como um chat inteligente, mas essa função está planejada.</li>
            <li>As informações são coletadas e apresentadas em tempo real, mas podem conter pequenos erros ou dados
                incompletos.</li>
            <li>Verifique sempre endereço, preço e detalhes antes de se programar.</li>
            <li>Estamos constantemente ajustando o sistema e contamos com a sua participação!</li>
        </ul>

        <p class="legenda-aviso alert alert-primary">
            ⚠️ Lembre-se: O sistema ainda está em construção. Sempre confira endereço, horário, preço e detalhes do
            evento diretamente com o local.
            Eventuais informações incompletas ou imprecisas podem aparecer.
        </p>

        <div class="legenda-container">
            <h2>Legenda e dicas do site</h2>

            <!-- Legenda de categorias -->
            <div class="legenda-categorias">
                <h3>📍 Categorias de eventos</h3>
                <ul>
                    <?php foreach ($CATEGORIAS_CORES as $categoria => $cor): ?>
                        <li>
                            <img src="https://maps.google.com/mapfiles/ms/icons/<?= htmlspecialchars($cor) ?>-dot.png"
                                alt="<?= htmlspecialchars($categoria) ?>" />
                            <?= htmlspecialchars($categoria) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <br>

            <!-- Legenda de botões -->
            <div class="legenda-botoes">
                <h3>💡 Botões e funcionalidades</h3>
                <ul>
                    <li><span class="btn-exemplo">?</span> Ajuda: clique para enviar feedbacks, sugestões ou relatar
                        erros (abre em nova aba).</li>
                    <li><span class="btn-exemplo">↓ / ↑</span> Toggle de filtros ou chat: mostra/esconde filtros de
                        data, categorias e a área de chat com a IA.</li>
                    <li><span class="btn-exemplo">💬</span> Chat IA: peça recomendações de eventos personalizadas para o
                        dia selecionado.</li>
                    <li><span class="btn-exemplo">Buscar</span> Botão de busca por data e hora: atualiza o mapa e
                        eventos filtrados.</li>
                    <li><span class="btn-exemplo">☰ / ✖</span> Toggle categorias: abre ou fecha a lista de categorias de
                        eventos no canto direito.</li>
                </ul>
            </div>

        </div>


        <h2>Área de Feedback</h2>
        <p>Você pode nos ajudar de três formas:</p>

        <?php if ($feedback_enviado): ?>
            <div class="alert alert-success">✅ Feedback enviado com sucesso! Obrigado pela colaboração.</div>
        <?php elseif ($feedback_erro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($feedback_erro) ?></div>
        <?php endif; ?>

        <!-- Formulário 1: Correção / Bug -->
        <form method="POST">
            <input type="hidden" name="tipo" value="correcao">
            <label>Relatar erro ou correção no site:</label>
            <textarea name="mensagem" rows="3" placeholder="Descreva o erro ou informação incorreta"></textarea>
            <button type="submit">Enviar Feedback</button>
        </form>

        <!-- Formulário 2: Opinião sobre evento / lugar -->
        <form method="POST">
            <input type="hidden" name="tipo" value="opiniao">
            <label>Opinião sobre eventos ou locais:</label>
            <textarea name="mensagem" rows="3"
                placeholder="Compartilhe sua opinião sobre um evento ou lugar"></textarea>
            <button type="submit">Enviar Opinião</button>
        </form>

        <!-- Formulário 3: Sugestão de novos eventos / casas -->
        <form method="POST">
            <input type="hidden" name="tipo" value="sugestao">
            <label>Sugira novos eventos ou casas para o radar:</label>
            <textarea name="mensagem" rows="3"
                placeholder="Digite eventos ou locais que gostaria de ver no site"></textarea>
            <button type="submit">Enviar Sugestão</button>
        </form>

        <p class="mt-4 text-center"><a href="index.php">← Voltar para a página inicial</a></p>
    </div>
</body>

</html>