<?php
require_once 'config/db.php';

try {
  $stmt = $conn->prepare('SELECT * FROM eventos limit 1');
} catch (PDOException $e) {
  echo "Erro: " . $e->getMessage();
}

// Filtro por data
date_default_timezone_set('America/Sao_Paulo');
$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$horaFiltro = $_GET['hora'] ?? '00:00';

// Preparar e executar query
$sql = "SELECT * FROM eventos WHERE DATE(data_evento) = :data AND TIME(data_evento) >= :hora";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':data', $dataFiltro);
$stmt->bindValue(':hora', $horaFiltro);
$stmt->execute();

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Qual a boa?! Eventos na Região</title>
  <link rel="icon" type="image/x-icon" href="./favicon_io/favicon.ico" />

  <!-- Bootstrap CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Fonte e CSS personalizado -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/main.css" />
</head>


<body class="text-center">
  <div id="regiao-1">
    <h1 class="text-primary fw-bold mt-3">Encontre Eventos</h1>
    <!-- <p class="text-center mb-3">Qual a boa do dia?!</p> -->
  </div>
  <div id="regiao-2">
    <!-- Filtro de data e hora -->
    <form method="GET" class="container-fluid pb-1 pt-1">
      <label class="col-7">Data: <input class="form-control" type="date" name="data"
          value="<?= htmlspecialchars($dataFiltro) ?>" /></label>
      <label class="col-5">Hora: <input class="form-control" type="time" name="hora"
          value="<?= htmlspecialchars($horaFiltro) ?>" /></label>
      <button type="submit" class="col-12 mt-2 btn btn-primary buscar-data">Buscar</button>
    </form>

    <!-- Mapa -->
    <div id="map" class="mt-2 mb-2"></div>

    <!-- Flyers -->
    <!-- <?php foreach ($eventos as $evento): ?>
      <div class="flyer">
        <h2><?= htmlspecialchars($evento['titulo']) ?></h2>
        <small><?= date("d/m/Y H:i", strtotime($evento['data_evento'])) ?></small>
        <div><?= $evento['flyer_html'] ?></div>
      </div>
    <?php endforeach; ?> -->
  </div>

  <div id="regiao-3" class="container-fluid">
    <!-- Chat com IA -->
    <div class="col-12 pd-2 chat-area">
      <h2 class="mb-0">O que está procurando?</h2>
      <small class="text-muted">"Sou a IA qual é a boa. Vou te ajudar achar o rolê!"</small>
      <div id="chatInput" class="chat-input">
        <input class="form-control mt-3" type="text" id="userInput"
          placeholder="Ex: Me indique algo no Gonzaga hoje à noite" />
        <button class="btn btn-secondary btn-chat col-12 mt-2" onclick="enviarPergunta()">Enviar</button>
      </div>
      <div id="recomendacoesChat" class="recomendacoes-container">
        <!-- Aqui os eventos recomendados serão inseridos -->
      </div>
      <p id="explicacaoIA" class="explicacao-chat"></p>

    </div>
  </div>
  <script>
    const eventos = <?= json_encode($eventos) ?>;
    let ordemPrioridade = [];

    function getFallbackLocation() {
      return { lat: -23.9608, lng: -46.3331 }; // Santos
    }

    function criarMapa(center) {
      return new google.maps.Map(document.getElementById("map"), {
        zoom: 10,
        scrollwheel: false,
        gestureHandling: "greedy",
        center,
      });
    }

    function criarMarker(map, pos, titulo, iconUrl, tamanho = 30) {
      return new google.maps.Marker({
        map,
        position: pos,
        title: titulo,
        icon: {
          url: iconUrl,
          scaledSize: new google.maps.Size(tamanho, tamanho)
        }
      });
    }

    function criarInfoWindow(evento) {
      const instagramTag = evento.linkInstagram
        ? `<a href="${evento.linkInstagram}" target="_blank">${evento.instagram}</a><br>`
        : evento.instagram
          ? `<span>${evento.instagram}</span><br>`
          : '';

      let flyerContent = '';

      if (evento.imagem_base64) {
        flyerContent = `<img src="data:image/png;base64,${evento.imagem_base64}" alt="Flyer" style="max-width: 80%;">`;
      } else if (evento.flyer_imagem) {
        flyerContent = `<img src="${evento.flyer_imagem}" alt="Flyer" style="max-width: 80%;">`;
      } else if (evento.flyer_html) {
        flyerContent = evento.flyer_html;
      }

      return `
        <div id="${evento.id}" style="max-width: 250px; font-family: Arial, sans-serif;">
          <h3 style="margin: 0 0 8px 0; font-size: 16px; color: #333;">${evento.titulo}</h3>
          <div style="max-height: 200px; overflow-y: auto; border-top: 1px solid #ccc; padding-top: 8px; font-size: 14px; color: #555;">
            ${flyerContent}
          </div>
          ${instagramTag}
        </div>
      `;
    }


    function getTamanhoPorPrioridade(id) {
      const index = ordemPrioridade.findIndex(item => parseInt(item) === parseInt(id));
      if (index === 0) return 50;
      if (index === 1) return 40;
      if (index === 2) return 35;
      return 30;
    }


    function adicionarEventoNoMapa(map, evento) {
      const infoContent = criarInfoWindow(evento);
      const tamanho = getTamanhoPorPrioridade(evento.id);

      const pos = evento.latitude && evento.longitude
        ? { lat: parseFloat(evento.latitude), lng: parseFloat(evento.longitude) }
        : null;

      if (!pos) return;

      const marker = criarMarker(map, pos, evento.titulo, "https://maps.google.com/mapfiles/ms/icons/red-dot.png", tamanho);
      const infoWindow = new google.maps.InfoWindow({ content: infoContent });

      marker.addListener("click", () => infoWindow.open(map, marker));
    }

    function mostrarLocalizacaoComAnimacao(map) {
      if (!navigator.geolocation) {
        console.warn("Geolocalização não suportada.");
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const userLoc = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
          };

          // // 🔵 Cria marcador azul
          // const marcador = criarMarker(
          //   map,
          //   userLoc,
          //   "Você está aqui",
          //   "https://maps.google.com/mapfiles/ms/icons/blue-dot.png",
          //   40
          // );

          // 🟦 Cria círculo de precisão
          const circulo = new google.maps.Circle({
            strokeColor: "#1E90FF",
            strokeOpacity: 0.6,
            strokeWeight: 2,
            fillColor: "#1E90FF",
            fillOpacity: 0.2,
            map: map,
            center: userLoc,
            radius: pos.coords.accuracy || 50,
          });

          map.setCenter(userLoc);

          // 🔄 Anima o raio do círculo
          let growing = true;
          let currentRadius = circulo.getRadius();

          setInterval(() => {
            currentRadius += growing ? 5 : -5;
            circulo.setRadius(currentRadius);

            if (currentRadius >= (pos.coords.accuracy + 30)) growing = false;
            if (currentRadius <= pos.coords.accuracy) growing = true;
          }, 80);
        },
        (err) => {
          console.warn("Erro ao obter localização:", err.message);
        }
      );
    }

    function initMap() {
      const fallbackLocation = getFallbackLocation();
      const map = criarMapa(fallbackLocation);

      mostrarLocalizacaoComAnimacao(map);

      eventos.forEach((evento) => {
        adicionarEventoNoMapa(map, evento);
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
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ pergunta, data, hora, eventos })
      })
        .then(res => res.json())
        .then(data => {
          let resposta;
          try {
            resposta = JSON.parse(data.resposta);
            resposta.ordem = (resposta.ordem || []).map(id => parseInt(id));
          } catch (e) {
            erro = data?.erro + "<br>" + data?.codigo;
            resposta = {
              ordem: [],
              explicacao: data.resposta || erro || "Resposta em formato inesperado"
            };
          }

          ordemPrioridade = resposta.ordem || [];
          document.getElementById("explicacaoIA").innerHTML = resposta.explicacao || "Sem resposta da IA";

          document.getElementById("recomendacoesChat").innerHTML = "";
          initMap();

          document.scrollingElement.scrollTo(0, 999999);
        });
    }
  </script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
</body>

</html>