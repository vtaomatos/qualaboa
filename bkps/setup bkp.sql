  -- Criar tabela chat_logs no SQLite
  DROP TABLE IF EXISTS chat_logs;
  
  CREATE TABLE chat_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pergunta TEXT NOT NULL,
    resposta TEXT NOT NULL,
    data_criacao TEXT NOT NULL  -- SQLite usa TEXT para datas/hora normalmente
  );
  
  -- Inserir dados
  INSERT INTO chat_logs (id, pergunta, resposta, data_criacao) VALUES
    (1, 'Algum pagode hoje?', 'Ainda estou aprendendo a responder sobre eventos com IA. Mas posso te ajudar a encontrar lugares legais.', '2025-05-22 18:21:26'),
    (2, 'Algum pagode hoje?', 'Ainda estou aprendendo a responder sobre eventos com IA. Mas posso te ajudar a encontrar lugares legais.', '2025-05-27 00:00:18');
  
  -- Criação da tabela 'eventos' em SQLite
  DROP TABLE IF EXISTS eventos;
  
  CREATE TABLE eventos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo TEXT NOT NULL,
    data_evento TEXT NOT NULL, -- SQLite usa TEXT para armazenar datas no formato ISO8601
    tipo_conteudo TEXT,
    flyer_html TEXT,
    flyer_imagem TEXT,
    latitude REAL,
    longitude REAL,
    criado_em TEXT NOT NULL DEFAULT (datetime('now'))
  );
  
  -- Inserção dos dados
  INSERT INTO eventos (id, titulo, data_evento, tipo_conteudo, flyer_html, flyer_imagem, latitude, longitude, criado_em) VALUES
  (11,'Festival de Verão - Orla','2025-05-28 00:00:00','html','<div style="background:orange;color:white;padding:10px;"><h2>Festival de Verão</h2><p>Na orla de Santos com música e dança</p></div>',NULL,-23.96750000,-46.32820000,'2025-05-17 03:26:41'),
  (12,'Samba no Centro','2025-05-28 00:00:00','html','<div style="background:#ffeaea;padding:10px;"><h3>Samba no Centro</h3><p>Roda de samba aberta com convidados</p></div>',NULL,-23.96180000,-46.33360000,'2025-05-17 03:26:41'),
  (13,'Pagode do Gonzaga','2025-05-28 19:00:00','html','<div style="color:red;">Pagode na Praça do Gonzaga</div>',NULL,-23.96710000,-46.32730000,'2025-05-17 03:26:41'),
  (14,'Roda de Samba no Valongo','2025-05-18 21:00:00','html','<div style="font-family:sans-serif;"><strong>Valongo recebe mais uma roda de samba</strong><br>Com participação especial do grupo Raiz</div>',NULL,-23.93160000,-46.33080000,'2025-05-17 03:26:41'),
  (15,'Funk no Porto','2025-05-18 22:00:00','html','<div style="background:black;color:lime;padding:10px;">Baile Funk no Porto - DJs convidados e pista livre!</div>',NULL,-23.93500000,-46.31350000,'2025-05-17 03:26:41'),
  (16,'Festival Black Santos','2025-05-19 20:00:00','html','<h2>Black Music Night</h2><p>Imperdível! RnB, Hip-Hop, Soul e muito mais!</p>',NULL,-23.95400000,-46.33520000,'2025-05-17 03:26:41'),
  (17,'Sexta do Groove - Boqueirão','2025-05-19 22:30:00','html','<div style="background:#222;color:#fff;padding:10px;"><h3>Sexta do Groove</h3><p>Boqueirão ao som do melhor do groove e funk retrô</p></div>',NULL,-23.96780000,-46.32470000,'2025-05-17 03:26:41'),
  (18,'Noite RnB & Soul','2025-05-20 21:00:00','html','<div style="border-left:5px solid purple;padding-left:10px;"><h4>Noite RnB & Soul</h4><p>Música boa e clima suave</p></div>',NULL,-23.96040000,-46.33250000,'2025-05-17 03:26:41'),
  (19,'Sarau na Ponta da Praia','2025-02-02 18:30:00','html','<p>Sarau cultural na areia com poesia, voz e violão</p>',NULL,-23.98110000,-46.29280000,'2025-05-17 03:26:41'),
  (20,'Carnaval Antecipado - Morro São Bento','2025-02-03 17:00:00','html','<div style="background:yellow;padding:5px;"><strong>Carnaval Antecipado!</strong><br>No Morro São Bento com trio elétrico e bloco!</div>',NULL,-23.94070000,-46.33670000,'2025-05-17 03:26:41'),
  (21,'Pagode no Deck 22','2025-05-22 20:00:00','','<div style="font-family: Arial, sans-serif;"><h3 style="margin-bottom: 5px;">? Pagode no Deck 22</h3><p>Venha curtir uma noite de pagode raiz com o grupo <strong>Sambaê</strong>. Muita música boa, petiscos e aquele clima de praia!</p><ul><li><strong>Horário:</strong> 20h às 00h</li><li><strong>Local:</strong> Deck 22 - Ponta da Praia</li><li><strong>Entrada:</strong> R$ 10,00</li></ul></div>','https://example.com/flyers/pagode-deck22.jpg',-23.96340000,-46.33120000,'2025-05-22 16:20:48'),
  (22,'Funk Sunset - Terraço da Praia','2025-05-22 18:00:00','','<div style="font-family: Arial, sans-serif;"><h3>? Funk Sunset no Terraço</h3><p>DJ’s ao vivo com muito funk carioca, clima de pôr do sol e bebidas geladas no Terraço da Praia.</p><ul><li><strong>Início:</strong> 18h</li><li><strong>Open Bar:</strong> até 19h</li><li><strong>Dress code:</strong> Casual/Verão</li></ul></div>','https://example.com/flyers/funk-sunset.jpg',-23.96150000,-46.32680000,'2025-05-22 16:20:48'),
  (23,'Roda de Samba - Boteco Boa Vista','2025-05-22 21:00:00','','<div style="font-family: Arial, sans-serif;"><h3>? Roda de Samba no Boteco Boa Vista</h3><p>Uma noite com grandes clássicos do samba em clima descontraído com artistas convidados.</p><ul><li><strong>Data:</strong> 22/05/2025</li><li><strong>Horário:</strong> 21h às 01h</li><li><strong>Ambiente:</strong> Coberto e com mesas ao ar livre</li></ul></div>','https://example.com/flyers/boa-vista.jpg',-23.95120000,-46.32850000,'2025-05-22 16:20:48'),
  (24,'Black Music Night - Valongo Lounge','2025-05-22 22:30:00','','<div style="font-family: Arial, sans-serif;"><h3>? Black Music Night</h3><p>Noite especial com muito R&B, Hip Hop, Soul e Funk no coração do Valongo.</p><ul><li><strong>Line-up:</strong> DJ Maju, DJ Léo Black</li><li><strong>Local:</strong> Valongo Lounge Bar</li><li><strong>Ingresso:</strong> R$ 15,00 na porta</li></ul></div>','https://example.com/flyers/black-valongo.jpg',-23.93600000,-46.32500000,'2025-05-22 16:20:48');