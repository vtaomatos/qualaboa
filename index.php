<?php
require_once 'config/db.php';

try {
    $stmt = $conn->prepare('SELECT * FROM eventos limit 1');
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
      <label class="col-7">Data: <input class="form-control" type="date" name="data" value="<?= htmlspecialchars($dataFiltro) ?>" /></label>
      <label class="col-5">Hora: <input class="form-control" type="time" name="hora" value="<?= htmlspecialchars($horaFiltro) ?>" /></label>
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
        <input class="form-control mt-3" type="text" id="userInput" placeholder="Ex: Me indique algo no Gonzaga hoje à noite" />
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

    function initMap() {
      const fallbackLocation = { lat: -23.9608, lng: -46.3331 }; // Santos

      const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 13,
        scrollwheel: false,
        gestureHandling: "greedy",
        center: fallbackLocation,
      });

      // Cria marcador com a nova API, usando fallback inicialmente
      let marker = new google.maps.Marker({
        map: map,
        position: fallbackLocation,
        title: "Localização inicial",
        icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
      });

      // Tenta pegar a localização do usuário
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            const userLocation = {
              lat: pos.coords.latitude,
              lng: pos.coords.longitude,
            };
            console.log("Pegou localização do usuário:", userLocation);

            // Atualiza centro e posição do marcador
            map.setCenter(userLocation);
            marker.position = userLocation;
            marker.title = "Você está aqui";
          },
          (err) => {
            console.warn("Erro ao obter localização:", err.message);
          }
        );
      } else {
        console.log("Geolocalização não suportada pelo navegador.");
      }


      const geocoder = new google.maps.Geocoder();


      eventos.forEach(e => {
        const instagramTag = e.linkInstagram && e.instagram ? `<a href="${e.linkInstagram}" target="_blank">${e.instagram}</a>` : '';

        const flyerBloob = e.imagem_base64 ? `<img src="data:image/png;base64,${e.imagem_base64}" alt="Flyer" style="max-width: 100%;">` : '';
        const flyer = e.flyer_imagem ? `<img src="admin/assets/uploads/${e.flyer_imagem}" alt="Flyer" style="max-width: 100%;">` : '';
        const flyerHtml = e.flyer_html ? `<div style="max-width: 100%;">${e.flyer_html}</div>` : '';
        
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
              ${flyerBloob ? flyerBloob : (flyer ? flyer : (flyerHtml ? flyerHtml : e.descricao))}
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

        console.log('latitude', e.latitude)
        console.log('longitude', e.longitude)

        // Se já tem coordenadas, usa direto
        if (e.latitude && e.longitude) {
          console.log('entrou no if')
          const pos = { lat: parseFloat(e.latitude), lng: parseFloat(e.longitude) };
          adicionarMarker(pos);
        } 
        // Senão, tenta geocodificar o endereço
        // else if (e.endereco) {
        //   console.log('endereco', e.endereco)
        //   geocoder.geocode({ address: e.endereco }, (results, status) => {
        //     if (status === 'OK' && results[0]) {
        //       const pos = results[0].geometry.location;
        //       console.log('pos', pos)
        //       adicionarMarker(pos);
        //     } else {
        //       console.warn(`Endereço "${e.endereco}" não encontrado: ${status}`);
        //     }
        //   });
        // }
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

        document.scrollingElement.scrollTo(0,999999);

      });

    }
  </script>
  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
</body>

</html>
