<?php
// Conexão com banco
$conn = new mysqli("localhost", "root", "", "qualaboa");
if ($conn->connect_error) die("Erro: " . $conn->connect_error);

// Filtro por data
$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$horaFiltro = $_GET['hora'] ?? '00:00';

// Buscar eventos
$stmt = $conn->prepare("SELECT * FROM eventos WHERE DATE(data_evento) = ? AND TIME(data_evento) >= ?");
$stmt->bind_param("ss", $dataFiltro, $horaFiltro);
$stmt->execute();
$result = $stmt->get_result();
$eventos = [];
while ($row = $result->fetch_assoc()) {
    $eventos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Eventos na Cidade</title>
  <style>
    #map { height: 400px; width: 100%; margin-bottom: 1rem; }
    .flyer { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
    .chat-area { margin-top: 20px; }
  </style>
</head>
<body>
  <h1>Veja os eventos na cidade</h1>

  <!-- Filtro de data e hora -->
  <form method="GET">
    <label>Data: <input type="date" name="data" value="<?= htmlspecialchars($dataFiltro) ?>" /></label>
    <label>Hora mínima: <input type="time" name="hora" value="<?= htmlspecialchars($horaFiltro) ?>" /></label>
    <button type="submit">Buscar</button>
  </form>

  <!-- Mapa -->
  <div id="map"></div>

  <!-- Flyers -->
  <!-- <?php foreach ($eventos as $evento): ?>
    <div class="flyer">
      <h2><?= htmlspecialchars($evento['titulo']) ?></h2>
      <small><?= date("d/m/Y H:i", strtotime($evento['data_evento'])) ?></small>
      <div><?= $evento['flyer_html'] ?></div>
    </div>
  <?php endforeach; ?> -->

  <!-- Chat com IA -->
  <div class="chat-area">
    <h2>Converse com a IA sobre os eventos</h2>
    <input type="text" id="userInput" placeholder="Ex: Me indique algo no Gonzaga hoje à noite" style="width:80%;" />
    <button onclick="enviarPergunta()">Enviar</button>
    <pre id="respostaIA"></pre>
  </div>

  <script>
    // Dados de eventos para JS
    const eventos = <?= json_encode($eventos) ?>;

    function initMap() {
      const centro = { lat: -23.9608, lng: -46.3331 }; // Santos
      const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 13,
        center: centro,
      });

      eventos.forEach(e => {
        const content = `
          <div style="max-width: 250px; font-family: Arial, sans-serif;">
            <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #333;">${e.titulo}</h3>
            <div style="
              max-height: 200px;
              overflow-y: auto;
              border-top: 1px solid #ccc;
              padding-top: 8px;
              font-size: 14px;
              color: #555;
            ">
              ${e.flyer_html}
            </div>
          </div>
        `;
        const pos = { lat: parseFloat(e.latitude), lng: parseFloat(e.longitude) };
        const marker = new google.maps.Marker({ position: pos, map, title: e.titulo });
        const info = new google.maps.InfoWindow({ content });
        marker.addListener("click", () => info.open(map, marker));
      });
    }

    function enviarPergunta() {
      const pergunta = document.getElementById("userInput").value;
      if (!pergunta.trim()) return;

      document.getElementById("respostaIA").textContent = "⏳ Pensando...";

      fetch("/api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ pergunta })
      })
      .then(res => res.json())
      .then(data => {
        console.log("Resposta bruta da IA:", data.debug); // 👈 Log no console
        document.getElementById("respostaIA").textContent = data.resposta || "Erro ao obter resposta";
      })
      .catch(err => {
        document.getElementById("respostaIA").textContent = "Erro: " + err.message;
      });
    }

  </script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
</body>
</html>
