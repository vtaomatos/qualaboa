<?php require_once("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = $_POST["titulo"];
    $data_evento = $_POST["data_evento"];
    $tipo_conteudo = $_POST["tipo_conteudo"];
    $flyer_html = ($tipo_conteudo === 'html') ? $_POST["flyer_html"] : null;
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $flyer_imagem = null;

    if ($tipo_conteudo === 'imagem' && isset($_FILES["flyer_imagem"])) {
        $nome_arquivo = time() . "_" . basename($_FILES["flyer_imagem"]["name"]);
        $caminho_destino = "../assets/uploads/" . $nome_arquivo;

        if (move_uploaded_file($_FILES["flyer_imagem"]["tmp_name"], $caminho_destino)) {
            $flyer_imagem = $nome_arquivo;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO eventos (titulo, data_evento, tipo_conteudo, flyer_html, flyer_imagem, latitude, longitude) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $data_evento, $tipo_conteudo, $flyer_html, $flyer_imagem, $latitude, $longitude]);

    echo "<p style='color:green;'>Evento cadastrado com sucesso!</p>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Evento</title>
</head>
<body>
    <h2>Cadastrar Evento</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Título:</label><br>
        <input type="text" name="titulo" required><br><br>

        <label>Data e Hora:</label><br>
        <input type="datetime-local" name="data_evento" required><br><br>

        <label>Tipo de Flyer:</label><br>
        <input type="radio" name="tipo_conteudo" value="imagem" checked onchange="toggleFlyer()"> Imagem
        <input type="radio" name="tipo_conteudo" value="html" onchange="toggleFlyer()"> HTML<br><br>

        <div id="imagem_input">
            <label>Upload da Imagem:</label><br>
            <input type="file" name="flyer_imagem" accept="image/*"><br><br>
        </div>

        <div id="html_input" style="display:none;">
            <label>Flyer HTML:</label><br>
            <textarea name="flyer_html" rows="5" cols="50"></textarea><br><br>
        </div>

        <label>Localização (Latitude / Longitude):</label><br>
        <input type="text" name="latitude" placeholder="-23.9618" required>
        <input type="text" name="longitude" placeholder="-46.3336" required><br><br>

        <button type="submit">Cadastrar</button>
    </form>

    <script>
        function toggleFlyer() {
            const tipo = document.querySelector('input[name="tipo_conteudo"]:checked').value;
            document.getElementById("imagem_input").style.display = tipo === "imagem" ? "block" : "none";
            document.getElementById("html_input").style.display = tipo === "html" ? "block" : "none";
        }

        window.onload = toggleFlyer;
    </script>
</body>
</html>
