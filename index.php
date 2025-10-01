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
$dataFormatada = htmlspecialchars($_GET['data'] ?? date('d/m/Y'));

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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

</head>


<body class="text-center">
  <div id="regiao-1" class="fixo-topo">
    <!-- <p class="text-center mb-3">Qual a boa do dia?!</p> -->
    <form id="form-regiao-1" method="GET" class="container-fluid pb-1 pt-1 regiao-colapsada">
      <label class="col-7">Data: <input class="form-control" type="date" name="data"
          value="<?= htmlspecialchars($dataFiltro) ?>" /></label>
      <label class="col-5">Hora: <input class="form-control" type="time" name="hora"
          value="<?= htmlspecialchars($horaFiltro) ?>" /></label>
      <button type="submit" class="col-12 mt-2 btn btn-primary buscar-data">Buscar</button>
    </form>
    <button class="toggle-btn" onclick="toggleRegiao('form-regiao-1',this)"> ↓ Encontre eventos na sua região por data:
      <?= $dataFormatada ?></button>
  </div>

  <div id="regiao-2">
    <!-- Filtro de data e hora -->

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

  <div id="regiao-3" class="fixo-rodape">
    <!-- Chat com IA -->
    <div id="chat-area" class="col-12 pd-2 chat-area regiao-colapsada">
      <!-- <h2 class="mb-0">Encontre Eventos Com IA</h2> -->
      <!-- <small class="text-muted">"Sou a IA qual é a boa. Vou te ajudar achar o rolê!"</small> -->
      <div id="chatInput" class="chat-input">
        <input class="form-control mt-3" type="text" id="userInput"
          placeholder="Ex: IA, quero curtir samba à noite." />
        <button class="btn btn-secondary btn-chat col-12 mt-2" onclick="enviarPergunta()">Enviar</button>
      </div>
      <div id="recomendacoesChat" class="recomendacoes-container">
        <!-- Aqui os eventos recomendados serão inseridos -->
      </div>
      <p id="explicacaoIA" class="explicacao-chat"></p>

    </div>
    <button class="toggle-btn" onclick="toggleRegiao('chat-area', this)">↑ Use IA para encontrar eventos no dia <?= $dataFormatada ?></button>
  </div>
  <div id="lightbox" onclick="fecharLightbox()">
    <img id="lightbox-img" src="" alt="Imagem Ampliada" />
  </div>

  <script>
    const dataFiltro = "<?= $dataFormatada ?>";
    const eventos = <?= json_encode($eventos) ?>;
    let ordemPrioridade = [];

    function getFallbackLocation() {
      return { lat: -23.9608, lng: -46.3331 }; // Santos
    }

    function criarMapa(center) {
      return new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        gestureHandling: "greedy",
        center,
        mapTypeControll:false,
        streetViewControl: false,
        zoomControl: true,
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
      const infoWindow = new google.maps.InfoWindow({ content: infoContent, disableAutoPan: false });

      marker.addListener("click", () => {
        infoWindow.open(map, marker);
        map.panBy(0, -250);
        google.maps.event.addListenerOnce(infoWindow, 'closeclick', () => {
          map.panBy(0, 250); // Reverte o deslocamento
        });
      });
    }

    function initMap() {
      const fallbackLocation = getFallbackLocation();
      const map = criarMapa(fallbackLocation);

      mostrarLocalizacaoComAnimacao(map);

      const eventosAgrupados = agruparEventosPorLocal(eventos);

      Object.keys(eventosAgrupados).forEach((key) => {
        const eventosDoLocal = eventosAgrupados[key];
        const exemploEvento = eventosDoLocal[0];

        const pos = {
          lat: parseFloat(exemploEvento.latitude),
          lng: parseFloat(exemploEvento.longitude)
        };

        const tamanho = getTamanhoPorPrioridade(exemploEvento.id);
        const marker = criarMarker(map, pos, exemploEvento.titulo, "https://maps.google.com/mapfiles/ms/icons/red-dot.png", tamanho);
        const infoWindow = new google.maps.InfoWindow({ disableAutoPan: false });

        marker.addListener("click", () => {
          const infoWindowContent = criarSliderEventos(eventosDoLocal);
          infoWindow.setContent(infoWindowContent);
          infoWindow.open(map, marker);
          map.panBy(0, -250);

          google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
            new Swiper('.swiper-container', {
              pagination: {
                el: '.swiper-pagination',
                clickable: true,
              },
            });
          });

          google.maps.event.addListenerOnce(infoWindow, 'closeclick', () => {
            map.panBy(0, 250);
          });
        });
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

    function toggleRegiao(regiaoId, btn) {
      const regiao = document.getElementById(regiaoId);
      const colapsada = regiao.classList.toggle('regiao-colapsada');

      const mostrarTexto = regiaoId === 'chat-area' ? `↑ Use IA para encontrar eventos no dia ${dataFiltro}` : `↓ Encontre eventos na sua região por data: ${dataFiltro}`;
      const ocultarTexto = regiaoId === 'chat-area' ? '↓ Ocultar' : '↑ Ocultar';

      btn.innerHTML = colapsada ? mostrarTexto : ocultarTexto;
    }

    function agruparEventosPorLocal(eventos) {
      const agrupados = {};
      eventos.forEach((evento) => {
        const key = `${parseFloat(evento.latitude).toFixed(5)}_${parseFloat(evento.longitude).toFixed(5)}`;
        if (!agrupados[key]) agrupados[key] = [];
        agrupados[key].push(evento);
      });
      return agrupados;
    }

function criarSliderEventos(eventos) {
  if (!eventos.length) return '';

  let slides = eventos.map((ev) => {
    const imagem = ev.imagem_base64
      ? `<img src="data:image/png;base64,${ev.imagem_base64}" class="slide-img" onclick="abrirLightbox(this)" style="max-width:70%">`
      : ev.flyer_imagem
        ? `<img src="${ev.flyer_imagem}" class="slide-img" onclick="abrirLightbox(this)" />`
        : ev.flyer_html || '';

    const instagram = ev.linkInstagram
      ? `<a href="${ev.linkInstagram}" target="_blank">${ev.instagram}</a><br>`
      : ev.instagram
        ? `<span>${ev.instagram}</span><br>`
        : '';

    return `
      <div class="swiper-slide" style="text-align:center">
        <h3 style="font-size:16px; margin-bottom:10px; white-space: normal; word-break: break-word; margin-right:15px">
          ${ev.titulo}
        </h3>
        ${imagem}
        <div style="margin-top:6px; padding-bottom:5px">${instagram}</div>
      </div>
    `;
  }).join('');

  return `
    <div class="swiper-container" style="width: 230px; max-width: 230px;">
      <div class="swiper-wrapper">
        ${slides}
      </div>
      <div class="swiper-pagination" style="margin-top: 5px;"></div>
    </div>
  `;
}

    function mostrarLocalizacaoComAnimacao(map) {
      if (!navigator.geolocation) return;

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const userLoc = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
          };

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



    marker.addListener("click", () => {
      const key = `${parseFloat(evento.latitude).toFixed(5)}_${parseFloat(evento.longitude).toFixed(5)}`;
      const eventosDoLocal = eventosAgrupados[key] || [];

      const infoWindowContent = criarSliderEventos(eventosDoLocal);
      infoWindow.setContent(infoWindowContent);
      infoWindow.open(map, marker);
      map.panBy(0, -250);

      google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
        new Swiper('.swiper-container', {
          pagination: { el: '.swiper-pagination', clickable: true },
        });
      });

      google.maps.event.addListenerOnce(infoWindow, 'closeclick', () => {
        map.panBy(0, 250);
      });
    });


    function abrirLightbox(imgElement) {
      document.getElementById('lightbox-img').src = imgElement.src;
      document.getElementById('lightbox').style.display = 'flex';
    }

    function fecharLightbox() {
      document.getElementById('lightbox').style.display = 'none';
    }

  </script>

  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</body>

</html>