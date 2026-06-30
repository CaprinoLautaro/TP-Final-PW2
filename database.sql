
CREATE DATABASE preguntados;

USE preguntados;

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE roles (
                       id      TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                       nombre  VARCHAR(30) NOT NULL
);

CREATE TABLE paises (
                        id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        nombre  VARCHAR(100) NOT NULL,
                        codigo  VARCHAR(5)   NOT NULL
);

CREATE TABLE usuarios (
                          id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                          nombre_completo     VARCHAR(150) NOT NULL,
                          anio_nacimiento     YEAR        NOT NULL,
                          sexo                ENUM('Masculino','Femenino','Prefiero no cargarlo') NOT NULL,
                          pais_id             INT UNSIGNED NOT NULL,
                          ciudad              VARCHAR(100) NOT NULL,
                          latitud             DECIMAL(10,7),
                          longitud            DECIMAL(10,7),
                          email               VARCHAR(150) NOT NULL UNIQUE,
                          contrasenia         VARCHAR(255) NOT NULL,
                          nombre_usuario      VARCHAR(60)  NOT NULL UNIQUE,
                          foto_perfil         VARCHAR(255),
                          rol_id              TINYINT UNSIGNED NOT NULL DEFAULT 1,
                          activo              TINYINT(1)  NOT NULL DEFAULT 0,
                          token_validacion    VARCHAR(100),
                          token_expira        DATETIME,
                          puntaje_total       INT UNSIGNED NOT NULL DEFAULT 0,
                          trampitas           INT UNSIGNED NOT NULL DEFAULT 0,
                          creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          nivel               ENUM('Malo', 'Bueno', 'Capo'),
                          FOREIGN KEY (pais_id) REFERENCES paises(id),
                          FOREIGN KEY (rol_id)  REFERENCES roles(id)

);

CREATE TABLE categorias (
                            id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            nombre  VARCHAR(80) NOT NULL,
                            color   VARCHAR(7)  NOT NULL,
                            activa  TINYINT(1)  NOT NULL DEFAULT 1
);

CREATE TABLE preguntas (
                           id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                           enunciado       TEXT NOT NULL,
                           categoria_id    INT UNSIGNED NOT NULL,
                           creado_por      INT UNSIGNED,
                           aprobada_por    INT UNSIGNED,

                           dificultad      ENUM('facil','media','dificil')
                               NOT NULL DEFAULT 'media',

                           estado          ENUM('pendiente','aprobada','rechazada','reportada')
                               NOT NULL DEFAULT 'pendiente',

                           veces_vista     INT UNSIGNED NOT NULL DEFAULT 0,
                           veces_correcta  INT UNSIGNED NOT NULL DEFAULT 0,
                           creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                           FOREIGN KEY (categoria_id) REFERENCES categorias(id),
                           FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
                           FOREIGN KEY (aprobada_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE opciones (
                          id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                          pregunta_id INT UNSIGNED NOT NULL,
                          texto       VARCHAR(255) NOT NULL,
                          es_correcta TINYINT(1)  NOT NULL DEFAULT 0,
                          orden       TINYINT     NOT NULL DEFAULT 0,
                          FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
);

CREATE TABLE partidas (
                          id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                          usuario_id      INT UNSIGNED NOT NULL,
                          puntaje         INT UNSIGNED NOT NULL DEFAULT 0,
                          estado ENUM('en_curso','terminada','abandonada') NOT NULL DEFAULT 'en_curso',
                          creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          terminada_en    DATETIME

);

CREATE TABLE partidas_preguntas (
                                    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    partida_id          INT UNSIGNED NOT NULL,
                                    pregunta_id         INT UNSIGNED NOT NULL,
                                    opcion_elegida_id   INT UNSIGNED,
                                    es_correcta         TINYINT(1) NOT NULL DEFAULT 0,
                                    uso_trampita        TINYINT(1) NOT NULL DEFAULT 0,
                                    respondida_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE preguntas_vistas (
                                  usuario_id  INT UNSIGNED NOT NULL,
                                  pregunta_id INT UNSIGNED NOT NULL,
                                  PRIMARY KEY (usuario_id, pregunta_id),
                                  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
                                  FOREIGN KEY (pregunta_id) REFERENCES preguntas(id) ON DELETE CASCADE
);

CREATE TABLE reportes_preguntas (
                                    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    pregunta_id INT UNSIGNED NOT NULL,
                                    usuario_id  INT UNSIGNED NOT NULL,
                                    motivo      TEXT,
                                    estado      ENUM('pendiente','resuelto') NOT NULL DEFAULT 'pendiente',
                                    revisado_por INT UNSIGNED,
                                    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (pregunta_id)  REFERENCES preguntas(id) ON DELETE CASCADE,
                                    FOREIGN KEY (usuario_id)   REFERENCES usuarios(id) ON DELETE CASCADE,
                                    FOREIGN KEY (revisado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE compras_trampitas (
                                   id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                   usuario_id      INT UNSIGNED NOT NULL,
                                   cantidad        INT UNSIGNED NOT NULL DEFAULT 1,
                                   monto_usd       DECIMAL(8,2) NOT NULL DEFAULT 1.00,
                                   estado_pago     ENUM('simulado','aprobado','rechazado','pendiente') NOT NULL DEFAULT 'simulado',
                                   referencia_pago VARCHAR(100),
                                   creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE contextos (
                           id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                           nombre           VARCHAR(150) NOT NULL,
                           codigo_qr        VARCHAR(100) NOT NULL UNIQUE,
                           duracion_minutos INT UNSIGNED NOT NULL DEFAULT 60,
                           activo           TINYINT(1)  NOT NULL DEFAULT 1,
                           creado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuarios_contextos (
                                    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                    usuario_id  INT UNSIGNED NOT NULL,
                                    contexto_id INT UNSIGNED NOT NULL,
                                    activado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    expira_en   DATETIME NOT NULL,
                                    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
                                    FOREIGN KEY (contexto_id) REFERENCES contextos(id) ON DELETE CASCADE
);

INSERT INTO paises (nombre, codigo) VALUES
                                        ('Argentina', 'AR'),
                                        ('Uruguay', 'UY'),
                                        ('Chile', 'CL'),
                                        ('Brasil', 'BR'),
                                        ('México', 'MX');

INSERT INTO roles (nombre)
VALUES
    ('usuario'),
    ('editor'),
    ('administrador');

INSERT INTO categorias (nombre, color, activa) VALUES
  ('Historia',   '#E74C3C', 1),  
  ('Ciencia',    '#3498DB', 1),   
  ('Deportes',   '#2ECC71', 1),  
  ('Geografía',  '#F39C12', 1),  
  ('Arte y Cultura', '#9B59B6', 1);

-- ============================================================
--  CATEGORÍA 1 — HISTORIA
-- ============================================================

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué año comenzó la Primera Guerra Mundial?', 1, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '1914', 1, 1),
                                                                  (LAST_INSERT_ID(), '1918', 0, 2),
                                                                  (LAST_INSERT_ID(), '1939', 0, 3),
                                                                  (LAST_INSERT_ID(), '1905', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Quién fue el primer presidente de los Estados Unidos?', 1, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'George Washington', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Abraham Lincoln',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Thomas Jefferson',  0, 3),
                                                                  (LAST_INSERT_ID(), 'Benjamin Franklin', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué año cayó el Muro de Berlín?', 1, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '1989', 1, 1),
                                                                  (LAST_INSERT_ID(), '1991', 0, 2),
                                                                  (LAST_INSERT_ID(), '1985', 0, 3),
                                                                  (LAST_INSERT_ID(), '1975', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué civilización construyó el Coliseo de Roma?', 1, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Los romanos',   1, 1),
                                                                  (LAST_INSERT_ID(), 'Los griegos',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Los egipcios',  0, 3),
                                                                  (LAST_INSERT_ID(), 'Los persas',    0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cómo se llamaba el barco en el que viajó Cristóbal Colón en su primer viaje?', 1, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Santa María', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Mayflower',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Pinta',       0, 3),
                                                                  (LAST_INSERT_ID(), 'Victoria',    0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué país estalló la Revolución Francesa?', 1, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Francia',      1, 1),
                                                                  (LAST_INSERT_ID(), 'Inglaterra',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Alemania',     0, 3),
                                                                  (LAST_INSERT_ID(), 'España',       0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Quién fue el líder de la Alemania Nazi durante la Segunda Guerra Mundial?', 1, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Adolf Hitler',      1, 1),
                                                                  (LAST_INSERT_ID(), 'Benito Mussolini',  0, 2),
                                                                  (LAST_INSERT_ID(), 'Heinrich Himmler', 0, 3),
                                                                  (LAST_INSERT_ID(), 'Joseph Goebbels',  0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué año llegó el hombre a la Luna por primera vez?', 1, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '1969', 1, 1),
                                                                  (LAST_INSERT_ID(), '1965', 0, 2),
                                                                  (LAST_INSERT_ID(), '1972', 0, 3),
                                                                  (LAST_INSERT_ID(), '1961', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué imperio fue gobernado por Gengis Kan?', 1, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Imperio Mongol',   1, 1),
                                                                  (LAST_INSERT_ID(), 'Imperio Otomano',  0, 2),
                                                                  (LAST_INSERT_ID(), 'Imperio Persa',    0, 3),
                                                                  (LAST_INSERT_ID(), 'Imperio Romano',   0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Quién escribió la Declaración de Independencia de los Estados Unidos?', 1, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Thomas Jefferson',  1, 1),
                                                                  (LAST_INSERT_ID(), 'George Washington', 0, 2),
                                                                  (LAST_INSERT_ID(), 'James Madison',     0, 3),
                                                                  (LAST_INSERT_ID(), 'John Adams',        0, 4);

-- ============================================================
--  CATEGORÍA 2 — CIENCIA
-- ============================================================

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es el símbolo químico del oro?', 2, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Au', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Ag', 0, 2),
                                                                  (LAST_INSERT_ID(), 'Fe', 0, 3),
                                                                  (LAST_INSERT_ID(), 'Go', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos huesos tiene el cuerpo humano adulto?', 2, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '206', 1, 1),
                                                                  (LAST_INSERT_ID(), '212', 0, 2),
                                                                  (LAST_INSERT_ID(), '198', 0, 3),
                                                                  (LAST_INSERT_ID(), '220', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué planeta es conocido como el planeta rojo?', 2, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Marte',   1, 1),
                                                                  (LAST_INSERT_ID(), 'Júpiter', 0, 2),
                                                                  (LAST_INSERT_ID(), 'Venus',   0, 3),
                                                                  (LAST_INSERT_ID(), 'Saturno', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es la velocidad de la luz en el vacío (aprox.)?', 2, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '300.000 km/s', 1, 1),
                                                                  (LAST_INSERT_ID(), '150.000 km/s', 0, 2),
                                                                  (LAST_INSERT_ID(), '500.000 km/s', 0, 3),
                                                                  (LAST_INSERT_ID(), '1.000 km/s',   0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué científico formuló la teoría de la relatividad?', 2, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Albert Einstein', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Isaac Newton',    0, 2),
                                                                  (LAST_INSERT_ID(), 'Nikola Tesla',    0, 3),
                                                                  (LAST_INSERT_ID(), 'Stephen Hawking', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es el elemento más abundante en la atmósfera terrestre?', 2, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Nitrógeno', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Oxígeno',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Hidrógeno', 0, 3),
                                                                  (LAST_INSERT_ID(), 'Argón',     0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué parte de la célula contiene el ADN?', 2, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'El núcleo',        1, 1),
                                                                  (LAST_INSERT_ID(), 'La mitocondria',   0, 2),
                                                                  (LAST_INSERT_ID(), 'El ribosoma',      0, 3),
                                                                  (LAST_INSERT_ID(), 'La membrana',      0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos cromosomas tiene una célula humana normal?', 2, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '46', 1, 1),
                                                                  (LAST_INSERT_ID(), '23', 0, 2),
                                                                  (LAST_INSERT_ID(), '48', 0, 3),
                                                                  (LAST_INSERT_ID(), '64', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué fuerza mantiene a los planetas en órbita alrededor del Sol?', 2, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'La gravedad',             1, 1),
                                                                  (LAST_INSERT_ID(), 'El magnetismo',           0, 2),
                                                                  (LAST_INSERT_ID(), 'La fuerza centrífuga',    0, 3),
                                                                  (LAST_INSERT_ID(), 'La fuerza electromagnética', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es la tabla periódica y quién la creó?', 2, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Ordenamiento de elementos químicos, creada por Mendeléiev', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Lista de minerales, creada por Newton',                     0, 2),
                                                                  (LAST_INSERT_ID(), 'Clasificación de planetas, creada por Galileo',             0, 3),
                                                                  (LAST_INSERT_ID(), 'Sistema de unidades, creado por Einstein',                  0, 4);

-- ============================================================
--  CATEGORÍA 3 — DEPORTES
-- ============================================================

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué país se originó el fútbol moderno?', 3, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Inglaterra', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Brasil',     0, 2),
                                                                  (LAST_INSERT_ID(), 'España',     0, 3),
                                                                  (LAST_INSERT_ID(), 'Italia',     0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos jugadores tiene un equipo de básquet en cancha?', 3, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '5', 1, 1),
                                                                  (LAST_INSERT_ID(), '6', 0, 2),
                                                                  (LAST_INSERT_ID(), '7', 0, 3),
                                                                  (LAST_INSERT_ID(), '4', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cada cuántos años se celebran los Juegos Olímpicos de verano?', 3, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Cada 4 años', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Cada 2 años', 0, 2),
                                                                  (LAST_INSERT_ID(), 'Cada 3 años', 0, 3),
                                                                  (LAST_INSERT_ID(), 'Cada 5 años', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos sets se juegan en un partido de tenis de Grand Slam masculino?', 3, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Al mejor de 5 sets', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Al mejor de 3 sets', 0, 2),
                                                                  (LAST_INSERT_ID(), 'Al mejor de 7 sets', 0, 3),
                                                                  (LAST_INSERT_ID(), 'Un solo set',        0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué deporte se usa el término "home run"?', 3, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Béisbol',   1, 1),
                                                                  (LAST_INSERT_ID(), 'Cricket',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Softball',  0, 3),
                                                                  (LAST_INSERT_ID(), 'Rounders',  0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos metros mide una piscina olímpica de natación?', 3, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '50 metros', 1, 1),
                                                                  (LAST_INSERT_ID(), '25 metros', 0, 2),
                                                                  (LAST_INSERT_ID(), '100 metros',0, 3),
                                                                  (LAST_INSERT_ID(), '75 metros', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué país ganó la Copa Mundial de Fútbol de 2022?', 3, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Argentina', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Francia',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Brasil',    0, 3),
                                                                  (LAST_INSERT_ID(), 'Croacia',   0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos puntos vale un try en rugby?', 3, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '5 puntos', 1, 1),
                                                                  (LAST_INSERT_ID(), '3 puntos', 0, 2),
                                                                  (LAST_INSERT_ID(), '6 puntos', 0, 3),
                                                                  (LAST_INSERT_ID(), '4 puntos', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué ciudad se celebraron los primeros Juegos Olímpicos modernos en 1896?', 3, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Atenas',  1, 1),
                                                                  (LAST_INSERT_ID(), 'París',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Londres', 0, 3),
                                                                  (LAST_INSERT_ID(), 'Roma',    0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos jugadores hay en un equipo de voleibol en cancha?', 3, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '6', 1, 1),
                                                                  (LAST_INSERT_ID(), '5', 0, 2),
                                                                  (LAST_INSERT_ID(), '7', 0, 3),
                                                                  (LAST_INSERT_ID(), '8', 0, 4);

-- ============================================================
--  CATEGORÍA 4 — GEOGRAFÍA
-- ============================================================

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es el río más largo del mundo?', 4, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'El Nilo',      1, 1),
                                                                  (LAST_INSERT_ID(), 'El Amazonas',  0, 2),
                                                                  (LAST_INSERT_ID(), 'El Yangtsé',   0, 3),
                                                                  (LAST_INSERT_ID(), 'El Mississippi',0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es el país más grande del mundo por superficie?', 4, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Rusia',  1, 1),
                                                                  (LAST_INSERT_ID(), 'China',  0, 2),
                                                                  (LAST_INSERT_ID(), 'Canadá', 0, 3),
                                                                  (LAST_INSERT_ID(), 'EE.UU.', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué continente está Egipto?', 4, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'África',   1, 1),
                                                                  (LAST_INSERT_ID(), 'Asia',     0, 2),
                                                                  (LAST_INSERT_ID(), 'Europa',   0, 3),
                                                                  (LAST_INSERT_ID(), 'Oceanía',  0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es la capital de Australia?', 4, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Canberra', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Sídney',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Melbourne',0, 3),
                                                                  (LAST_INSERT_ID(), 'Brisbane', 0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué océano es el más grande del mundo?', 4, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Océano Pacífico',  1, 1),
                                                                  (LAST_INSERT_ID(), 'Océano Atlántico', 0, 2),
                                                                  (LAST_INSERT_ID(), 'Océano Índico',    0, 3),
                                                                  (LAST_INSERT_ID(), 'Océano Ártico',    0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es la montaña más alta del mundo?', 4, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Monte Everest', 1, 1),
                                                                  (LAST_INSERT_ID(), 'K2',           0, 2),
                                                                  (LAST_INSERT_ID(), 'Aconcagua',    0, 3),
                                                                  (LAST_INSERT_ID(), 'Mont Blanc',   0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué país está la Torre Eiffel?', 4, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Francia',    1, 1),
                                                                  (LAST_INSERT_ID(), 'Italia',     0, 2),
                                                                  (LAST_INSERT_ID(), 'Bélgica',    0, 3),
                                                                  (LAST_INSERT_ID(), 'Suiza',      0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuántos países forman América del Sur?', 4, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), '12', 1, 1),
                                                                  (LAST_INSERT_ID(), '10', 0, 2),
                                                                  (LAST_INSERT_ID(), '14', 0, 3),
                                                                  (LAST_INSERT_ID(), '9',  0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es el desierto más grande del mundo?', 4, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'La Antártida', 1, 1),
                                                                  (LAST_INSERT_ID(), 'El Sahara',    0, 2),
                                                                  (LAST_INSERT_ID(), 'El Gobi',      0, 3),
                                                                  (LAST_INSERT_ID(), 'El Atacama',   0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué país tiene más fronteras terrestres con otros países?', 4, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'China / Rusia (14 cada uno)', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Brasil',                      0, 2),
                                                                  (LAST_INSERT_ID(), 'Alemania',                    0, 3),
                                                                  (LAST_INSERT_ID(), 'Sudan',                       0, 4);

-- ============================================================
--  CATEGORÍA 5 — ARTE Y CULTURA
-- ============================================================

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Quién pintó la Mona Lisa?', 5, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Leonardo da Vinci', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Miguel Ángel',      0, 2),
                                                                  (LAST_INSERT_ID(), 'Rafael',            0, 3),
                                                                  (LAST_INSERT_ID(), 'Botticelli',        0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Quién escribió "Don Quijote de la Mancha"?', 5, 'aprobada', 'facil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Miguel de Cervantes', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Lope de Vega',        0, 2),
                                                                  (LAST_INSERT_ID(), 'Francisco de Quevedo',0, 3),
                                                                  (LAST_INSERT_ID(), 'Garcilaso de la Vega',0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué país nació el músico Wolfgang Amadeus Mozart?', 5, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Austria',    1, 1),
                                                                  (LAST_INSERT_ID(), 'Alemania',   0, 2),
                                                                  (LAST_INSERT_ID(), 'Francia',    0, 3),
                                                                  (LAST_INSERT_ID(), 'Italia',     0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cómo se llama la famosa escultura de Miguel Ángel que representa a un joven bíblico?', 5, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'El David',     1, 1),
                                                                  (LAST_INSERT_ID(), 'La Piedad',    0, 2),
                                                                  (LAST_INSERT_ID(), 'El Moisés',    0, 3),
                                                                  (LAST_INSERT_ID(), 'El Baco',      0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Quién compuso la Quinta Sinfonía?', 5, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Beethoven', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Mozart',    0, 2),
                                                                  (LAST_INSERT_ID(), 'Bach',      0, 3),
                                                                  (LAST_INSERT_ID(), 'Schubert',  0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Cuál es la obra teatral más famosa de William Shakespeare?', 5, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Hamlet',          1, 1),
                                                                  (LAST_INSERT_ID(), 'Romeo y Julieta', 0, 2),
                                                                  (LAST_INSERT_ID(), 'Macbeth',         0, 3),
                                                                  (LAST_INSERT_ID(), 'Otelo',           0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿En qué ciudad está el Museo del Prado?', 5, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Madrid',    1, 1),
                                                                  (LAST_INSERT_ID(), 'Barcelona', 0, 2),
                                                                  (LAST_INSERT_ID(), 'Sevilla',   0, 3),
                                                                  (LAST_INSERT_ID(), 'Lisboa',    0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué movimiento artístico lideró Salvador Dalí?', 5, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Surrealismo',  1, 1),
                                                                  (LAST_INSERT_ID(), 'Cubismo',      0, 2),
                                                                  (LAST_INSERT_ID(), 'Impresionismo',0, 3),
                                                                  (LAST_INSERT_ID(), 'Dadaísmo',     0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Quién escribió "Cien años de soledad"?', 5, 'aprobada', 'media', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Gabriel García Márquez', 1, 1),
                                                                  (LAST_INSERT_ID(), 'Mario Vargas Llosa',     0, 2),
                                                                  (LAST_INSERT_ID(), 'Jorge Luis Borges',      0, 3),
                                                                  (LAST_INSERT_ID(), 'Julio Cortázar',         0, 4);

INSERT INTO preguntas (enunciado, categoria_id, estado, dificultad, veces_vista, veces_correcta) VALUES
    ('¿Qué arquitecto diseñó la Sagrada Familia de Barcelona?', 5, 'aprobada', 'dificil', 0, 0);
INSERT INTO opciones (pregunta_id, texto, es_correcta, orden) VALUES
                                                                  (LAST_INSERT_ID(), 'Antoni Gaudí',      1, 1),
                                                                  (LAST_INSERT_ID(), 'Le Corbusier',      0, 2),
                                                                  (LAST_INSERT_ID(), 'Frank Lloyd Wright',0, 3),
                                                                  (LAST_INSERT_ID(), 'Renzo Piano',       0, 4);
INSERT INTO usuarios (
    nombre_completo,
    anio_nacimiento,
    sexo,
    pais_id,
    ciudad,
    email,
    contrasenia,
    nombre_usuario,
    rol_id,
    activo,
    nivel
) VALUES (
             'Admin General',                                              -- nombre_completo
             1995,                                                         -- anio_nacimiento
             'Prefiero no cargarlo',                                       -- sexo
             1,                                                            -- pais_id (verificá que exista ese id en `paises`)
             'Buenos Aires',                                               -- ciudad
             'admin@preguntados.com',                                      -- email (tiene que ser único)
             '$2b$10$kFiGEZ6d3Cz3VPa0wFBb2eD8Seb290cS/UNWKV42zVBG9r6anl2vG', -- contrasenia = "Admin123!"
             'admin',                                                      -- nombre_usuario (único)
             3,                                                            -- rol_id = 3 -> administrador
             1,                                                            -- activo = 1 (para no tener que validar por mail)
             'Capo'                                                        -- nivel
         );

INSERT INTO usuarios (
    nombre_completo,
    anio_nacimiento,
    sexo,
    pais_id,
    ciudad,
    email,
    contrasenia,
    nombre_usuario,
    rol_id,
    activo,
    nivel
) VALUES (
             'Editor general',
             1995,                                                         -
             'Prefiero no cargarlo',
             1,
             'Buenos Aires',
             'editor@preguntados.com',
             '$2b$10$kFiGEZ6d3Cz3VPa0wFBb2eD8Seb290cS/UNWKV42zVBG9r6anl2vG', -- contrasenia = "Admin123!"
             'editor',
             2,
             1,
             'Capo'
         );

SET FOREIGN_KEY_CHECKS = 1;
