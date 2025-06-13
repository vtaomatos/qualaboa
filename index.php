<?php
// Conexão com banco SQLite
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
    $stmt = $pdo->prepare('SELECT * FROM eventos');
    $stmt->execute();
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

// Filtro por data
date_default_timezone_set('America/Sao_Paulo');
$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$horaFiltro = $_GET['hora'] ?? '00:00';

// Preparar e executar query
$sql = "SELECT * FROM eventos WHERE DATE(data_evento) = :data AND TIME(data_evento) >= :hora";
$stmt = $pdo->prepare($sql);  // aqui, trocar $conn por $pdo
$stmt->bindValue(':data', $dataFiltro);
$stmt->bindValue(':hora', $horaFiltro);
$stmt->execute();

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="pt-br">

  <head>
    <meta charset="UTF-8" />
    <title>Eventos na Cidade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
      body {
        font-family: 'Inter', sans-serif;
        background-color: #f9f9f9;
        margin: 0;
        padding: 2rem;
        color: #333;
      }

      h1, h2 {
        text-align: center;
        color: #222;
      }

      form {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin: 1rem auto;
        flex-wrap: wrap;
        max-width: 800px;
      }

      label {
        display: flex;
        flex-direction: column;
        font-size: 0.9rem;
        color: #444;
      }

      input[type="date"],
      input[type="time"] {
        padding: 0.5rem;
        border: 1px solid #ccc;
        border-radius: 6px;
      }

      button {
        padding: 0.5rem 1rem;
        background-color: #4caf50;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
      }

      button:hover {
        background-color: #43a047;
      }

      #map {
        height: 400px;
        width: 100%;
        max-width: 1000px;
        margin: 1rem auto;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      }

      .chat-area {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 1rem;
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      }

      #userInput {
        width: 70%;
        padding: 0.6rem;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-right: 0.5rem;
      }

      .recomendacoes-container {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
      }

      .evento-card {
        background-color: #f0f0f0;
        padding: 1rem;
        border-radius: 12px;
        transition: transform 0.3s;
        flex: 1 1 220px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      }

      .evento-card.destacado-1 {
        transform: scale(1.1);
        background-color: #ffe8cc;
      }

      .evento-card.destacado-2 {
        transform: scale(1.05);
        background-color: #fff4e6;
      }

      .explicacao-chat {
        margin-top: 1rem;
        font-style: italic;
        color: #555;
      }

      @media (max-width: 600px) {
        form {
          flex-direction: column;
          align-items: center;
        }

        #userInput {
          width: 100%;
          margin-bottom: 0.5rem;
        }

        button {
          width: 100%;
        }
      }
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
    <input type="text" id="userInput" placeholder="Ex: Me indique algo no Gonzaga hoje à noite"
      style="width:80%;" />
    <button onclick="enviarPergunta()">Enviar</button>
    <div id="recomendacoesChat" class="recomendacoes-container">
      <!-- Aqui os eventos recomendados serão inseridos -->
    </div>
    <p id="explicacaoIA" class="explicacao-chat"></p>

  </div>

  <script>
    const eventos = <?= json_encode($eventos) ?>;

    let ordemPrioridade = [];

    function initMap() {
      const centro = { lat: -23.9608, lng: -46.3331 }; // Santos
      const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 13,
        center: centro,
      });

      const geocoder = new google.maps.Geocoder();


      eventos.forEach(e => {
        const instagramTag = e.linkInstagram && e.instagram ? `<a href="${e.linkInstagram}" target="_blank">${e.instagram}</a>` : '';

        const flyer = e.flyer_imagem ? `<img src="admin/assets/uploads/${e.flyer_imagem}" alt="Flyer" style="max-width: 100%;">` : `${e.flyer_html}`;

        const content = `
          <div id="${e.id}" style="max-width: 250px; font-family: Arial, sans-serif;">
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
            `+ instagramTag +`
          </div>
        `;

        const prioridadeIndex = ordemPrioridade.indexOf(e.id);

        let tamanho = 30;
        if (prioridadeIndex === 0) tamanho = 50;
        else if (prioridadeIndex === 1) tamanho = 40;
        else if (prioridadeIndex === 2) tamanho = 35;

        // Função para criar marcador
        const adicionarMarker = (pos) => {
          const marker = new google.maps.Marker({
            position: pos,
            map,
            title: e.titulo,
            icon: {
              url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
              scaledSize: new google.maps.Size(tamanho, tamanho)
            }
          });

          const info = new google.maps.InfoWindow({ content });
          marker.addListener("click", () => info.open(map, marker));
        };

        // Se já tem coordenadas, usa direto
        if (e.latitude && e.longitude) {
          console.log('entrou no if')
          const pos = { lat: parseFloat(e.latitude), lng: parseFloat(e.longitude) };
          adicionarMarker(pos);
        } 
        // Senão, tenta geocodificar o endereço
        else if (e.endereco) {
          console.log('endereco', e.endereco)
          geocoder.geocode({ address: e.endereco }, (results, status) => {
            if (status === 'OK' && results[0]) {
              const pos = results[0].geometry.location;
              console.log('pos', pos)
              adicionarMarker(pos);
            } else {
              console.warn(`Endereço "${e.endereco}" não encontrado: ${status}`);
            }
          });
        }
      });
    }

    function enviarPergunta() {
      const pergunta = document.getElementById("userInput").value;
      const data = document.querySelector("input[name='data']").value;
      const hora = document.querySelector("input[name='hora']").value;

      if (!pergunta.trim()) return;

      document.getElementById("explicacaoIA").textContent = "⏳ Pensando...";

      fetch("/api/chat.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ pergunta, data, hora, eventos}),
      })
        .then(res => res.json())
      .then(data => {
        let resposta;
        try {
          resposta = JSON.parse(data.resposta);
        } catch (e) {
          resposta = {
            ordem: [],
            explicacao: data.resposta || "Resposta em formato inesperado"
          };
        }

        const ordemPrioridade = resposta.ordem || [];

        const explicacao = resposta.explicacao || "Sem resposta da IA";
        document.getElementById("explicacaoIA").innerHTML = explicacao;

        const container = document.getElementById("recomendacoesChat");
        container.innerHTML = "";

        initMap();
      });

    }
  </script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
</body>

</html>
