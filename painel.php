<?php

session_start();
require_once './config/db.php';
date_default_timezone_set('America/Sao_Paulo');

/***********************
 * LOGOUT
 ***********************/
if (!empty($_GET['logout'])) {
  session_unset();      // limpa variáveis da sessão
  session_destroy();    // destrói a sessão
  header('Location: painel.php'); // ou login.php
  exit;
}

if (isset($_POST['login'])) {
  $stmt = $conn->prepare("SELECT * FROM usuarios WHERE login = ?");
  $stmt->execute([$_POST['login']]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($_POST['senha'], $user['senha'])) {
    $_SESSION['user'] = $user;
    header('Location: painel.php');
    exit;
  }

  $erro = 'Login inválido';
}


$logado = isset($_SESSION['user']);

if (!$logado): ?>

  <!-- TELA DE LOGIN -->
  <!doctype html>
  <html lang="pt-br">

  <head>
    <meta charset="utf-8">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>

  <body class="container py-5">

    <div class="card mx-auto p-4" style="max-width:400px">
      <h5 class="mb-3 text-center">Entrar no painel</h5>

      <form method="post" action="painel.php">
        <div class="mb-2">
          <label>Usuário</label>
          <input name="login" class="form-control" required>
        </div>

        <div class="mb-3">
          <label>Senha</label>
          <input type="password" name="senha" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Entrar</button>
      </form>
    </div>

  </body>

  </html>

  <?php exit; ?>

<?php endif;


$user = $_SESSION['user'];
$instagram = $user['instagram'];
$uid = $user['id'];

/***********************
 * AÇÕES
 ***********************/
if (!empty($_GET['cancelar'])) {
  header('Location: painel.php');
  exit;
}

if (!empty($_GET['deletar'])) {
  $conn->prepare("UPDATE eventos SET deletado=1, deletado_at=NOW() WHERE id=? AND instagram=?")
    ->execute([$_GET['deletar'], $instagram]);
  header('Location: painel.php');
  exit;
}

if (!empty($_GET['toggle'])) {
  $conn->prepare("UPDATE eventos SET visivel=NOT visivel WHERE id=? AND instagram=?")
    ->execute([$_GET['toggle'], $instagram]);
  header('Location: painel.php');
  exit;
}

/***********************
 * SALVAR
 ***********************/
if (!empty($_POST['salvar'])) {
  if (!empty($_POST['id'])) {
    $stmt = $conn->prepare("
        UPDATE eventos SET
          titulo=?, descricao=?, data_evento=?, data_fim_evento=?,
          endereco=?, latitude=?, longitude=?
        WHERE id=? AND instagram=?
      ");
    $stmt->execute([
      $_POST['titulo'],
      $_POST['descricao'],
      $_POST['data_inicio'],
      $_POST['data_fim_evento'],
      $_POST['endereco'],
      $_POST['latitude'],
      $_POST['longitude'],
      $_POST['id'],
      $instagram
    ]);
    $eid = $_POST['id'];
  } else {
    $stmt = $conn->prepare("
        INSERT INTO eventos
        (instagram,titulo,descricao,data_evento,data_fim_evento,endereco,latitude,longitude)
        VALUES (?,?,?,?,?,?,?,?)
      ");
    $stmt->execute([
      $instagram,
      $_POST['titulo'],
      $_POST['descricao'],
      $_POST['data_inicio'],
      $_POST['data_fim_evento'] ?? date('Y-m-d H:i:s', strtotime('+5 hours')),
      $_POST['endereco'],
      $_POST['latitude'],
      $_POST['longitude']
    ]);
    $eid = $conn->lastInsertId();
  }

  if (!empty($_FILES['flyer']['tmp_name'])) {
    $base64 = base64_encode(file_get_contents($_FILES['flyer']['tmp_name']));
    $conn->prepare("UPDATE eventos SET imagem_base64=? WHERE id=?")
      ->execute([$base64, $eid]);
  }

  header('Location: painel.php');
  exit;
}

/***********************
 * EDITAR
 ***********************/
$edit = null;
if (!empty($_GET['editar'])) {
  $stmt = $conn->prepare("SELECT * FROM eventos WHERE id=? AND instagram=? AND deletado=0");
  $stmt->execute([$_GET['editar'], $instagram]);
  $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/***********************
 * FILTROS
 ***********************/

$filtros = [];
$params = [$instagram];

if (!empty($_GET['f_data'])) {
  $filtros[] = "DATE(data_evento) = ?";
  $params[] = $_GET['f_data'];
}

if (!empty($_GET['f_texto'])) {
  $filtros[] = "(titulo LIKE ? OR descricao LIKE ?)";
  $params[] = '%' . $_GET['f_texto'] . '%';
  $params[] = '%' . $_GET['f_texto'] . '%';
}

$whereExtra = $filtros ? ' AND ' . implode(' AND ', $filtros) : '';


/***********************
 * PAGINAÇÃO
 ***********************/
$porPagina = 10;
$p = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($p - 1) * $porPagina;

$total = $conn->prepare("
  SELECT COUNT(*) 
  FROM eventos 
  WHERE instagram=? AND deletado=0 $whereExtra
");
$total->execute($params);
$totalPaginas = ceil($total->fetchColumn() / $porPagina);

$stmt = $conn->prepare("
  SELECT * FROM eventos
  WHERE instagram=? AND deletado=0 $whereExtra
  ORDER BY data_evento DESC
  LIMIT $porPagina OFFSET $offset
");
$stmt->execute($params);

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <title>Painel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .preview {
      aspect-ratio: 9/16;
      background: #eee;
      display: flex;
      align-items: center;
      justify-content: center
    }

    .preview img {
      width: 100%;
      height: 100%;
      object-fit: cover
    }

    .thumb {
      width: 70px;
      height: 70px;
      object-fit: cover
    }

    .modal-dialog {
      max-width: 420px
    }

    .badge-visivel {
      background: #d1e7dd;
      color: #0f5132
    }

    .badge-oculto {
      background: #e2e3e5;
      color: #41464b
    }

    .img-highlight {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .85);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    .img-highlight img {
      max-height: 90vh;
      aspect-ratio: 9 / 16;
      object-fit: contain;
      border-radius: 8px;
    }

    .img-highlight button {
      position: absolute;
      top: 20px;
      right: 25px;
      font-size: 22px;
    }

    .descricao-modal {
      background-color: rgba(0, 0, 0, .1);

    }

    #mDesc {
      background-color: rgba(0, 0, 0, .05);
      border-left: 5px solid rgba(0, 0, 0, .1);
      border-right: 5px solid rgba(0, 0, 0, .1);
    }

    .card-evento {
      cursor: pointer;
      transition: background-color .15s ease, transform .15s ease;
    }

    .card-evento:hover {
      background-color: rgba(0, 0, 0, .03);
    }
  </style>
</head>

<body class="container py-3">

  <!-- TOPO -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <strong><?= $instagram ?></strong>
      <small class="text-muted">(<?= $user['login'] ?> · ID <?= $uid ?>)</small>
    </div>
    <a href="?logout=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('Deseja sair do painel?')">
      Sair
    </a>
  </div>

  <!-- FORM -->
  <form method="post" enctype="multipart/form-data" class="card p-3 mb-4">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

    <div class="row g-3">
      <div class="col-md-4">
        <div class="preview position-relative mb-2">
          <button type="button" onclick="removerImagem()" class="btn btn-sm btn-dark position-absolute top-0 end-0 m-1"
            style="border-radius:50%;line-height:1;display:none" id="btnRemoveImg">✕</button>

          <img id="previewImg" src="<?= !empty($edit['imagem_base64'])
            ? 'data:image/jpeg;base64,' . $edit['imagem_base64']
            : '/imagens/sem_imagem.jpg' ?>">
        </div>

        <label class="fw-semibold" for="flyer">Imagem do evento</label>
        <input type="file" name="flyer" class="form-control" onchange="previewImagem(this)">
      </div>

      <div class="col-md-8">
        <label class="fw-semibold" for="titulo">Título</label>
        <input class="form-control mb-2" name="titulo" placeholder="Título" value="<?= $edit['titulo'] ?? '' ?>"
          required>

        <label class="fw-semibold" for="descricao">Descrição</label>
        <textarea class="form-control mb-2" name="descricao"
          style="height:120px"><?= $edit['descricao'] ?? '' ?></textarea>

        <div class="row">
          <div class="col">
            <label class="fw-semibold">Início</label>
            <input type="datetime-local" class="form-control" name="data_inicio"
              value="<?= isset($edit) ? date('Y-m-d\TH:i', strtotime($edit['data_evento'])) : date('Y-m-d\TH:00') ?>">
          </div>
          <div class="col">
            <label class="fw-semibold">Fim</label>
            <input type="datetime-local" class="form-control" name="data_fim_evento"
              value="<?= isset($edit) ? date('Y-m-d\TH:i', strtotime($edit['data_fim_evento'])) : date('Y-m-d\TH:00', strtotime('+5 hours')) ?>">
          </div>
        </div>
        <div class="row mt-2">
          <div class="col-md-12">
            <label class="fw-semibold">Endereço</label>
            <input type="text" class="form-control mb-2" name="endereco" value="<?= $edit['endereco'] ?? '' ?>">
          </div>

          <div class="col-md-6">
            <label class="fw-semibold">Latitude</label>
            <input type="text" class="form-control" name="latitude" value="<?= $edit['latitude'] ?? '' ?>">
          </div>

          <div class="col-md-6">
            <label class="fw-semibold">Longitude</label>
            <input type="text" class="form-control" name="longitude" value="<?= $edit['longitude'] ?? '' ?>">
          </div>
        </div>

      </div>
    </div>

    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-success" name="salvar" value="1">Salvar</button>
      <a href="?cancelar=1" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>

  <form method="get" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="fw-semibold">Filtrar por data</label>
        <input type="date" name="f_data" class="form-control" value="<?= htmlspecialchars($_GET['f_data'] ?? '') ?>">
      </div>

      <div class="col-md-6">
        <label class="fw-semibold">Buscar por nome ou descrição</label>
        <input type="text" name="f_texto" class="form-control" placeholder="Digite parte do título ou descrição"
          value="<?= htmlspecialchars($_GET['f_texto'] ?? '') ?>">
      </div>

      <div class="col-md-2 d-grid">
        <button class="btn btn-primary">Filtrar</button>
      </div>
    </div>
  </form>

  <!-- LISTAGEM -->
  <?php foreach ($eventos as $e):
    $img = $e['imagem_base64'] ? "data:image/jpeg;base64,{$e['imagem_base64']}" : "/imagens/sem_imagem.jpg";
    ?>
    <div class="card mb-2 p-2 d-flex flex-row align-items-center gap-2">

      <!-- ÁREA CLICÁVEL -->
      <div class="d-flex align-items-center gap-2 flex-fill abrir-modal" <?php //data-bs-toggle="modal" data-bs-target="#modal" ?> data-img="<?= $img ?>" data-titulo="<?= htmlspecialchars($e['titulo']) ?>"
        data-desc="<?= htmlspecialchars($e['descricao']) ?>"
        data-inicio="<?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?>"
        data-fim="<?= date('d/m/Y H:i', strtotime($e['data_fim_evento'])) ?>"
        data-endereco="<?= htmlspecialchars($e['endereco']) ?>" data-lat="<?= $e['latitude'] ?>"
        data-lng="<?= $e['longitude'] ?>">

        <img src="<?= $img ?>" class="thumb">

        <div>
          <strong><?= $e['titulo'] ?></strong><br>
          <small><?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?></small>
        </div>

      </div>

      <!-- STATUS -->
      <span class="badge <?= $e['visivel'] ? 'badge-visivel' : 'badge-oculto' ?>">
        <?= $e['visivel'] ? 'Visível' : 'Oculto' ?>
      </span>

      <!-- BOTÕES (FORA da área clicável) -->
      <div class="btn-group">
        <a href="?editar=<?= $e['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
        <a href="?toggle=<?= $e['id'] ?>"
          class="btn btn-sm btn-<?= $e['visivel'] ? 'secondary' : 'success' ?>"><?= $e['visivel'] ? 'Ocultar' : 'Mostrar' ?></a>
        <a href="?deletar=<?= $e['id'] ?>" onclick="return confirm('Excluir evento?')"
          class="btn btn-sm btn-danger">Excluir</a>
      </div>

    </div>

  <?php endforeach; ?>

  <?php if ($totalPaginas > 1): ?>
    <nav class="mt-3">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
          <li class="page-item <?= $i == $p ? 'active' : '' ?>">
            <?php
            $q = $_GET;
            unset($q['p']);
            ?>
            <a class="page-link" href="?p=<?= $i ?>&<?= http_build_query($q) ?>">
              <?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>


  <!-- MODAL -->
  <div class="modal fade" id="modal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center">
          <div class="preview mb-2"><img id="mImg" onclick="abrirHighlight(this.src)"></div>
          <h6 id="mTitulo"></h6>
          <span class="text-dark d-block descricao-modal">Descrição</span>
          <p id="mDesc" class="bg-light pt-2"></p>
          <div class="mt-2 text-start small">
            <strong>Endereço:</strong>
            <div id="mEndereco" class="mb-1"></div>

            <strong>Coordenadas:</strong>
            <div id="mCoords"></div>

            <strong>Data Inicio:</strong>
            <div id="mInicio"></div>

            <strong>Data Fim:</strong>
            <div id="mFim"></div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="img-highlight" id="imgHighlight" onclick="fecharHighlight()">
    <button class="btn btn-light" onclick="fecharHighlight()">✕</button>
    <img id="imgHighlightSrc">
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('modal').addEventListener('show.bs.modal', e => {
      const d = e.relatedTarget.dataset

      mImg.src = d.img
      mTitulo.innerText = d.titulo
      mDesc.innerText = d.desc || '—'
      mInicio.innerText = 'Início: ' + d.inicio
      mFim.innerText = 'Fim: ' + d.fim

      mEndereco.innerText = d.endereco || '—'
      mCoords.innerText = (d.lat && d.lng)
        ? `${d.lat}, ${d.lng}`
        : '—'
    })


    function previewImagem(input) {
      if (input.files && input.files[0]) {
        previewImg.src = URL.createObjectURL(input.files[0])
        btnRemoveImg.style.display = 'block'
      }
    }

    function removerImagem() {
      previewImg.src = '/imagens/sem_imagem.jpg'
      btnRemoveImg.style.display = 'none'
      document.querySelector('input[name="flyer"]').value = ''
    }

    function abrirHighlight(src) {
      const overlay = document.getElementById('imgHighlight')
      const img = document.getElementById('imgHighlightSrc')
      if (src.includes('/imagens/sem_imagem.jpg')) return
      img.src = src
      overlay.style.display = 'flex'
    }

    function fecharHighlight() {
      document.getElementById('imgHighlight').style.display = 'none'
    }
  </script>

</body>

</html>