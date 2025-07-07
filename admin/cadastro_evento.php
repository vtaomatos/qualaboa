<?php
require_once("../secretsConstants.php");
require_once("../config/db.php");

session_start();
$ip = $_SERVER['REMOTE_ADDR'];
$tempo_bloqueio = 20 * 60; // 20 minutos

$_SESSION['tentativas'] = $_SESSION['tentativas'] ?? [];
$_SESSION['bloqueio'] = $_SESSION['bloqueio'] ?? [];

$bloqueado = false;

if (isset($_SESSION['bloqueio'][$ip])) {
    $tempo_passado = time() - $_SESSION['bloqueio'][$ip];
    if ($tempo_passado < $tempo_bloqueio) {
        $bloqueado = true;
        $min_restantes = ceil(($tempo_bloqueio - $tempo_passado) / 60);
    } else {
        unset($_SESSION['bloqueio'][$ip]);
        $_SESSION['tentativas'][$ip] = 0;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {
    if (!$bloqueado) {
        $usuario = $_POST["usuario"] ?? '';
        $senha = $_POST["senha"] ?? '';

        if ($usuario === HARDCODEUSER && $senha === HARDCODEPASS) {
            $_SESSION["logado"] = true;
        } else {
            $_SESSION['tentativas'][$ip] = ($_SESSION['tentativas'][$ip] ?? 0) + 1;
            if ($_SESSION['tentativas'][$ip] >= 3) {
                $_SESSION['bloqueio'][$ip] = time();
                $bloqueado = true;
                $min_restantes = ceil($tempo_bloqueio / 60);
            }
            $erro_login = "Usuário ou senha incorretos.";
        }
    }
}

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: evento.php");
    exit;
}
?>

<?php if (!isset($_SESSION["logado"])): ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f2f2f2; display: flex; justify-content: center; align-items: center; height: 100vh; }
        form { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        h2 { margin-bottom: 20px; text-align: center; }
        input, button { width: 260px; padding: 10px; margin: 10px; }
        .erro { color: red; text-align: center; }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Login</h2>
        <?php if ($bloqueado): ?>
            <p class="erro">Bloqueado por 20 minutos. Tente em <?= $min_restantes ?> min.</p>
        <?php else: ?>
            <?php if (!empty($erro_login)) echo "<p class='erro'>$erro_login</p>"; ?>
            <input type="text" name="usuario" placeholder="Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit" name="login">Entrar</button>
        <?php endif; ?>
    </form>
</body>
</html>

<?php else: ?>
<?php require_once("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["titulo"])) {
    $titulo = $_POST["titulo"];
    $data_evento = $_POST["data_evento"];
    $descricao = $_POST["descricao"];
    $endereco = $_POST["endereco"];
    $tipo_conteudo = $_POST["tipo_conteudo"];
    $flyer_html = ($tipo_conteudo === 'html') ? $_POST["flyer_html"] : null;
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $flyer_imagem = null;

    if ($tipo_conteudo === 'imagem' && isset($_FILES["flyer_imagem"])) {
        $nome_arquivo = time() . "_" . basename($_FILES["flyer_imagem"]["name"]);
        $caminho_destino = "assets/uploads/" . $nome_arquivo;

        if (move_uploaded_file($_FILES["flyer_imagem"]["tmp_name"], $caminho_destino)) {
            $flyer_imagem = $nome_arquivo;
        }
    }

    $stmt = $conn->prepare("INSERT INTO eventos (titulo, data_evento, descricao, endereco, tipo_conteudo, flyer_html, flyer_imagem, latitude, longitude) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $data_evento, $descricao, $endereco, $tipo_conteudo, $flyer_html, $flyer_imagem, $latitude, $longitude]);

    $sucesso = "Evento cadastrado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Evento</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f2f5, #e0e7ff);
            padding: 40px;
            margin: 0;
        }

        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 16px;
            max-width: 600px;
            margin: 40px auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            font-weight: 600;
        }

        label {
            font-weight: 500;
            color: #444;
            display: block;
            margin-bottom: 6px;
            margin-top: 12px;
        }

        input[type="text"],
        input[type="datetime-local"],
        input[type="password"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 12px 14px;
            margin: 5px 0 15px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 15px;
            transition: border-color 0.2s ease;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }

        textarea {
            resize: vertical;
        }

        button {
            background-color: #4f46e5;
            color: #fff;
            border: none;
            padding: 14px;
            margin-top: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease, box-shadow 0.2s;
        }

        button:hover {
            background-color: #4338ca;
            box-shadow: 0 4px 12px rgba(67, 56, 202, 0.2);
        }

        .sucesso {
            color: #16a34a;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }

        .erro {
            color: #dc2626;
            font-weight: bold;
            text-align: center;
            margin-top: 10px;
        }

        .sair {
            text-align: center;
            margin-bottom: 20px;
        }

        .sair a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }

        .sair a:hover {
            text-decoration: underline;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
            margin: 10px 0 20px;
        }

        .radio-group label {
            margin: 0;
        }
    </style>

    <script>
        function toggleFlyer() {
            const tipo = document.querySelector('input[name="tipo_conteudo"]:checked').value;
            document.getElementById("imagem_input").style.display = tipo === "imagem" ? "block" : "none";
            document.getElementById("html_input").style.display = tipo === "html" ? "block" : "none";
        }
        window.onload = toggleFlyer;
    </script>
</head>
<body>

    <form method="POST" enctype="multipart/form-data">
        <h2>Cadastrar Evento</h2>

        <?php if (!empty($sucesso)) echo "<p class='sucesso'>$sucesso</p>"; ?>

        <label>Título:</label>
        <input type="text" name="titulo" required>

        <label>Data e Hora:</label>
        <input type="datetime-local" name="data_evento" required>

        <label>Descrição:</label>
        <textarea name="descricao" rows="4" placeholder="Descreva o evento" required></textarea>

        <label>Endereço:</label>
        <input type="text" name="endereco" placeholder="Rua, número, cidade" required>

        <label>Tipo de Flyer:</label>
        <input type="radio" name="tipo_conteudo" value="imagem" checked onchange="toggleFlyer()"> Imagem
        <input type="radio" name="tipo_conteudo" value="html" onchange="toggleFlyer()"> HTML

        <div id="imagem_input">
            <label>Upload da Imagem:</label>
            <input type="file" name="flyer_imagem" accept="image/*">
        </div>

        <div id="html_input" style="display:none;">
            <label>Flyer HTML:</label>
            <textarea name="flyer_html" rows="5"></textarea>
        </div>

        <label>Latitude:</label>
        <input type="text" name="latitude" placeholder="-23.9618" required>

        <label>Longitude:</label>
        <input type="text" name="longitude" placeholder="-46.3336" required>

        <button type="submit">Cadastrar</button>

        <div class="sair"><a href="?logout=1">Sair</a></div>

    </form>
</body>
</html>

<?php endif; ?>