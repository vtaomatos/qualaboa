-- Tabela chat_logs
DROP TABLE IF EXISTS chat_logs;

CREATE TABLE chat_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  pergunta TEXT NOT NULL,
  resposta TEXT NOT NULL,
  data_criacao TEXT NOT NULL
);

INSERT INTO chat_logs (id, pergunta, resposta, data_criacao) VALUES
  (1, 'Algum pagode hoje?', 'Ainda estou aprendendo a responder sobre eventos com IA. Mas posso te ajudar a encontrar lugares legais.', '2025-05-22 18:21:26'),
  (2, 'Algum pagode hoje?', 'Ainda estou aprendendo a responder sobre eventos com IA. Mas posso te ajudar a encontrar lugares legais.', '2025-05-27 00:00:18');

-- Tabela eventos
DROP TABLE IF EXISTS eventos;

CREATE TABLE eventos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo TEXT NOT NULL,
  data_evento TEXT NOT NULL,
  tipo_conteudo TEXT,
  flyer_html TEXT,
  flyer_imagem TEXT,
  instagram TEXT,
  linkInstagram TEXT,
  latitude REAL,
  longitude REAL,
  criado_em TEXT NOT NULL DEFAULT (datetime('now')),
  descricao TEXT,
  endereco TEXT
);

-- Eventos já existentes (IDs 11–24)
INSERT INTO eventos (id, titulo, data_evento, tipo_conteudo, flyer_html, flyer_imagem, latitude, longitude, criado_em, descricao, endereco) VALUES
(11,'Festival de Verão - Orla','2025-05-28 00:00:00','html','<div style="background:orange;color:white;padding:10px;"><h2>Festival de Verão</h2><p>Na orla de Santos com música e dança</p></div>',NULL,-23.9675,-46.3282,'2025-05-17 03:26:41','Festival de música ao ar livre com vários artistas locais.','Orla da Praia de Santos'),
(12,'Samba no Centro','2025-05-28 00:00:00','html','<div style="background:#ffeaea;padding:10px;"><h3>Samba no Centro</h3><p>Roda de samba aberta com convidados</p></div>',NULL,-23.9618,-46.3336,'2025-05-17 03:26:41','Roda de samba na praça central com convidados especiais.','Praça Independência - Centro'),
(13,'Pagode do Gonzaga','2025-05-28 19:00:00','html','<div style="color:red;">Pagode na Praça do Gonzaga</div>',NULL,-23.9671,-46.3273,'2025-05-17 03:26:41','Pagode animado na tradicional Praça do Gonzaga.','Praça do Gonzaga'),
(14,'Roda de Samba no Valongo','2025-05-18 21:00:00','html','<div style="font-family:sans-serif;"><strong>Valongo recebe mais uma roda de samba</strong><br>Com participação especial do grupo Raiz</div>',NULL,-23.9316,-46.3308,'2025-05-17 03:26:41','Roda de samba com o grupo Raiz em ambiente histórico.','Rua do Comércio, Valongo'),
(15,'Funk no Porto','2025-05-18 22:00:00','html','<div style="background:black;color:lime;padding:10px;">Baile Funk no Porto - DJs convidados e pista livre!</div>',NULL,-23.9350,-46.3135,'2025-05-17 03:26:41','Baile funk com DJs locais no terminal portuário.','Terminal Marítimo - Porto de Santos'),
(16,'Festival Black Santos','2025-05-19 20:00:00','html','<h2>Black Music Night</h2><p>Imperdível! RnB, Hip-Hop, Soul e muito mais!</p>',NULL,-23.9540,-46.3352,'2025-05-17 03:26:41','Festival de black music com artistas independentes.','Arena Santos'),
(17,'Sexta do Groove - Boqueirão','2025-05-19 22:30:00','html','<div style="background:#222;color:#fff;padding:10px;"><h3>Sexta do Groove</h3><p>Boqueirão ao som do melhor do groove e funk retrô</p></div>',NULL,-23.9678,-46.3247,'2025-05-17 03:26:41','Festa com groove retrô na região do Boqueirão.','Av. Conselheiro Nébias, Boqueirão'),
(18,'Noite RnB & Soul','2025-05-20 21:00:00','html','<div style="border-left:5px solid purple;padding-left:10px;"><h4>Noite RnB & Soul</h4><p>Música boa e clima suave</p></div>',NULL,-23.9604,-46.3325,'2025-05-17 03:26:41','Noite com clássicos do RnB e soul em clima intimista.','Bar do Zé - Canal 3'),
(19,'Sarau na Ponta da Praia','2025-02-02 18:30:00','html','<p>Sarau cultural na areia com poesia, voz e violão</p>',NULL,-23.9811,-46.2928,'2025-05-17 03:26:41','Sarau com poesia e música na areia da praia.','Ponta da Praia'),
(20,'Carnaval Antecipado - Morro São Bento','2025-02-03 17:00:00','html','<div style="background:yellow;padding:5px;"><strong>Carnaval Antecipado!</strong><br>No Morro São Bento com trio elétrico e bloco!</div>',NULL,-23.9407,-46.3367,'2025-05-17 03:26:41','Bloco carnavalesco com trio elétrico e muita folia.','Morro São Bento'),
(21,'Pagode no Deck 22','2025-05-22 20:00:00','html','<div style="font-family: Arial, sans-serif;"><h3>Pagode no Deck 22</h3><p>Venha curtir uma noite de pagode raiz com o grupo <strong>Sambaê</strong>. Muita música boa, petiscos e aquele clima de praia!</p><ul><li><strong>Horário:</strong> 20h às 00h</li><li><strong>Local:</strong> Deck 22 - Ponta da Praia</li><li><strong>Entrada:</strong> R$ 10,00</li></ul></div>','https://example.com/flyers/pagode-deck22.jpg',-23.9634,-46.3312,'2025-05-22 16:20:48','Noite de pagode raiz com clima de praia.','Deck 22 - Ponta da Praia'),
(22,'Funk Sunset - Terraço da Praia','2025-05-22 18:00:00','html','<div style="font-family: Arial, sans-serif;"><h3>Funk Sunset no Terraço</h3><p>DJ’s ao vivo com muito funk carioca, clima de pôr do sol e bebidas geladas no Terraço da Praia.</p><ul><li><strong>Início:</strong> 18h</li><li><strong>Open Bar:</strong> até 19h</li><li><strong>Dress code:</strong> Casual/Verão</li></ul></div>','https://example.com/flyers/funk-sunset.jpg',-23.9615,-46.3268,'2025-05-22 16:20:48','Sunset de funk carioca no terraço com vista para o mar.','Terraço da Praia - Santos'),
(23,'Roda de Samba - Boteco Boa Vista','2025-06-06 21:00:00','html','<div style="font-family: Arial, sans-serif;"><h3>Roda de Samba no Boteco Boa Vista</h3><p>Uma noite com grandes clássicos do samba em clima descontraído com artistas convidados.</p><ul><li><strong>Data:</strong> 22/05/2025</li><li><strong>Horário:</strong> 21h às 01h</li><li><strong>Ambiente:</strong> Coberto e com mesas ao ar livre</li></ul></div>','https://example.com/flyers/boa-vista.jpg',-23.9512,-46.3285,'2025-05-22 16:20:48','Clássicos do samba em ambiente descontraído.','Boteco Boa Vista'),
(24,'Black Music Night - Valongo Lounge','2025-05-22 22:30:00','html','<div style="font-family: Arial, sans-serif;"><h3>Black Music Night</h3><p>Noite especial com muito R&B, Hip Hop, Soul e Funk no coração do Valongo.</p><ul><li><strong>Line-up:</strong> DJ Maju, DJ Léo Black</li><li><strong>Local:</strong> Valongo Lounge Bar</li><li><strong>Ingresso:</strong> R$ 15,00 na porta</li></ul></div>','https://example.com/flyers/black-valongo.jpg',-23.9360,-46.3250,'2025-05-22 16:20:48','Festa de black music com DJs renomados.','Valongo Lounge Bar');

-- Novos eventos com lat/long e Instagrams
INSERT INTO eventos (
  titulo, descricao, data_evento, tipo_conteudo, flyer_html, flyer_imagem,
  latitude, longitude, criado_em, endereco, instagram, linkInstagram
) VALUES
('Quintal do Moby - Pop/Axé/Pagode',
 'Música ao vivo toda quinta com pop, axé e pagode.',
 '2025-06-12 20:00:00','html','',NULL,
 -23.9716,-46.3269,'2025-06-06 00:00:00',
 'Av. Vicente de Carvalho, 30 - Boqueirão, Santos, SP',
 '@mobydicksantos','https://www.instagram.com/mobydicksantos'),

('Moby na Pegada - Sertanejo',
 'Sertanejo ao vivo toda sexta.',
 '2025-06-13 21:00:00','html','',NULL,
 -23.9716,-46.3269,'2025-06-06 00:00:00',
 'Av. Vicente de Carvalho, 30 - Boqueirão, Santos, SP',
 '@mobydicksantos','https://www.instagram.com/mobydicksantos'),

('Santo Moby Cabaret - MPB/Soul',
 'MPB e soul ao vivo todo sábado.',
 '2025-06-14 22:00:00','html','',NULL,
 -23.9716,-46.3269,'2025-06-06 00:00:00',
 'Av. Vicente de Carvalho, 30 - Boqueirão, Santos, SP',
 '@mobydicksantos','https://www.instagram.com/mobydicksantos'),

('Boteco do Moby - Samba/Pagode',
 'Roda de samba e pagode ao vivo todo domingo.',
 '2025-06-15 18:00:00','html','',NULL,
 -23.9716,-46.3269,'2025-06-06 00:00:00',
 'Av. Vicente de Carvalho, 30 - Boqueirão, Santos, SP',
 '@mobydicksantos','https://www.instagram.com/mobydicksantos'),

('Voz e Violão no Donna G Santos',
 'Música com voz e violão (MPB/pop); feijoada aos sábados.',
 '2025-06-14 12:00:00','html','',NULL,
 -23.9338792,-46.3270776,'2025-06-06 00:00:00',
 'Rua Itororó, 19 - Centro, Santos, SP',
 '@donnag.santos','https://www.instagram.com/donnag.santos'),

('Música ao Vivo no Meu Lugar',
 'Bandas locais, MPB/samba/pagode possíveis; quinta-feira ao domingo.',
 '2025-06-12 20:00:00','html','',NULL,
 -23.964925422301427,-46.3094853841148,'2025-06-06 00:00:00',
 'Rua Almirante Tamandaré, 284 - Estuário, Santos, SP',
 '@meulugar.bar','https://www.instagram.com/meulugar.bar'),

('Feijoada com Música ao Vivo',
 'Feijoada de sábado com MPB e samba/pagode possíveis.',
 '2025-06-14 12:00:00','html','',NULL,
 -23.964925422301427,-46.3094853841148,'2025-06-06 00:00:00',
 'Rua Almirante Tamandaré, 284 - Estuário, Santos, SP',
 '@meulugar.bar','https://www.instagram.com/meulugar.bar'),

('Luau Toatoa - DJ e Open Bar',
 'Luau com DJ, aula de axé e open bar na sexta.',
 '2025-06-13 21:00:00','html','',NULL,
 -23.971768,-46.357525,'2025-06-06 00:00:00',
 'Rua Antônio Guenaga, 62 - Santos, SP',
 '@toatoa_oficial','https://www.instagram.com/toatoa_oficial'),

('Felipe Kot & Niny Magalhães - PERRECO LOVE',
 'Show eletrônico/pop confirmado via Sympla.',
 '2025-06-24 22:00:00','html','',NULL,
 -23.971768,-46.357525,'2025-06-06 00:00:00',
 'Rua Antonio Guenaga, 62 - Santos, SP',
 '@toatoa_oficial','https://www.instagram.com/toatoa_oficial');

INSERT INTO eventos (
  titulo, data_evento, tipo_conteudo, flyer_html, flyer_imagem,
  latitude, longitude, descricao, endereco
) VALUES
-- Missas na Catedral de Santos
('Missa Dominical na Catedral – Domingo 09h', '2025-06-15 09:00:00', 'html',
 '<p>Missa dominical na Catedral de Nossa Senhora do Rosário às 09h.</p>', NULL,
 -23.9366450467105, -46.32437621692497, 'Missa católica aberta ao público todas as manhãs de domingo.', 'Praça Patriarca José Bonifácio, s/nº, Centro, Santos – SP'),

('Missa Dominical na Catedral – Domingo 18h', '2025-06-15 18:00:00', 'html',
 '<p>Missa dominical na Catedral de Nossa Senhora do Rosário às 18h.</p>', NULL,
 -23.9366450467105, -46.32437621692497, 'Missa vespertina dominical.', 'Praça Patriarca José Bonifácio, s/nº, Centro, Santos – SP'),

-- Cultos na Cristo é a Resposta
('Culto de Oração – Cristo é a Resposta (Terça 20h)', '2025-06-17 20:00:00', 'imagem',
 NULL, 'https://cristoearesposta.com.br/wp-content/uploads/2025/06/culto-terca-20h.jpg',
 -23.952154720434788, -46.32639081258822, 'Culto de Oração todas as terças às 20h. Igreja acessível, com intérprete de Libras.', 'Av. Washington Luís, 136, Canal 3, Santos – SP'),

('Culto de Celebração – Cristo é a Resposta (Domingo 10h45)', '2025-06-15 10:45:00', 'html',
 '<p>Culto de Celebração com Escola Dominical aos domingos às 10h45.</p>', NULL,
 -23.952154720434788, -46.32639081258822, 'Celebração dominical com Escola Dominical para crianças.', 'Av. Washington Luís, 136, Canal 3, Santos – SP'),

('Culto Espaço Jovem – Cristo é a Resposta (Sábado 20h)', '2025-06-14 20:00:00', 'imagem',
 NULL, 'https://cristoearesposta.com.br/wp-content/uploads/2025/06/culto-espaco-jovem.jpg',
 -23.952154720434788, -46.32639081258822, 'Culto dedicado ao público jovem aos sábados às 20h.', 'Av. Washington Luís, 136, Canal 3, Santos – SP'),

-- Cultos na Igreja Luterana de Santos
('Culto Luterano – 1º/3º Domingo 19h', '2025-06-01 19:00:00', 'html',
 '<p>Culto luterano celebrado no 1º e 3º domingo do mês às 19h.</p>', NULL,
 -23.9641872, -46.3489439, 'Culto da Paróquia Evangélica Luterana de Santos.', 'Avenida General Francisco Glicério, 626, Santos – SP'),

('Culto Luterano – 2º/4º Domingo 10h', '2025-06-08 10:00:00', 'html',
 '<p>Culto luterano nos 2º e 4º domingos do mês às 10h.</p>', NULL,
 -23.9641872, -46.3489439, 'Culto dominical, segundo padrão IECLB.', 'Avenida General Francisco Glicério, 626, Santos – SP'),

-- Cultos na Assembleia de Deus - Macuco
('Culto da Assembleia – Domingo 09h', '2025-06-15 09:00:00', 'html',
 '<p>Culto dominical com ensino bíblico às 09h.</p>', NULL,
 -23.956860487518345, -46.31868107962193, 'Culto na Assembleia de Deus, bairro Macuco.', 'Av. Siqueira Campos, 161, Macuco, Santos – SP'),

('Culto de Doutrina – Terça 19h45', '2025-06-17 19:45:00', 'html',
 '<p>Culto de Doutrina nas terças-feiras às 19h45.</p>', NULL,
 -23.956860487518345, -46.31868107962193, 'Reunião de estudo bíblico e doutrina.', 'Av. Siqueira Campos, 161, Macuco, Santos – SP');



-- Tabela usuarios
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  login TEXT NOT NULL,
  senha TEXT NOT NULL
);

INSERT INTO usuarios (login, senha) VALUES
('admin', '1234'),
('vitor', 'senha123');

DROP TABLE IF EXISTS locais;

CREATE TABLE locais (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nome TEXT,
  endereco TEXT NOT NULL,
  latitude REAL,
  longitude REAL,
  instagram TEXT,
  linkInstagram TEXT
);

INSERT INTO locais (nome, endereco, latitude, longitude, instagram, linkInstagram) VALUES
(
  'Moby Club',
  'Avenida Vicente de Carvalho, 30, Boqueirão, Santos – SP, 11045-500',
  -23.9716,
  -46.3269,
  '@mobydicksantos',
  'https://www.instagram.com/mobydicksantos'
),
(
  'Tô a Toa Bar C7',
  'Rua Antônio Guenaga, 62, Santos – SP, 11030-420',
  -23.971768,
  -46.357525,
  '@toatoabarc7',
  'https://www.instagram.com/toatoabarc7'
),
(
  'Donna G Santos',
  'Rua Itororó, 19, Centro, Santos – SP, 11010-070',
  -23.9338792,
  -46.3270776,
  '@donnag.santos',
  'https://www.instagram.com/donnag.santos'
),
(
  'Meu Lugar Bar',
  'Rua Almirante Tamandaré, 284, Estuário, Santos – SP, 11010-200',
  -23.964925422301427,
  -46.3094853841148,
  '@meulugar.bar',
  'https://www.instagram.com/meulugar.bar'
),
(
  'Casa Cinza',
  'Rua da Paz, 51, Boqueirão, Santos – SP, 11045-520',
  -23.7903953,
  -46.7449966,
  '@cinza.company',
  'https://www.instagram.com/cinza.company'
);

INSERT INTO locais (nome, endereco, latitude, longitude) VALUES
('Catedral de Santos', 'Praça Patriarca José Bonifácio, s/nº, Centro, Santos – SP', -23.9366450467105, -46.32437621692497),
('Cristo é a Resposta', 'Av. Washington Luís, 136, Canal 3, Santos – SP', -23.952154720434788, -46.32639081258822),
('Igreja Luterana de Santos', 'Avenida General Francisco Glicério, 626, Santos – SP', -23.9641872, -46.3489439),
('Assembleia de Deus Macuco', 'Av. Siqueira Campos, 161, Macuco, Santos – SP', -23.956860487518345, -46.31868107962193);

