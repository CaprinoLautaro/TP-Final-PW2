
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
                           enunciado       TEXT         NOT NULL,
                           categoria_id    INT UNSIGNED NOT NULL,
                           creado_por      INT UNSIGNED,
                           aprobada_por    INT UNSIGNED,
                           estado          ENUM('pendiente','aprobada','rechazada','reportada') NOT NULL DEFAULT 'pendiente',
                           veces_vista     INT UNSIGNED NOT NULL DEFAULT 0,
                           veces_correcta  INT UNSIGNED NOT NULL DEFAULT 0,
                           creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           FOREIGN KEY (categoria_id) REFERENCES categorias(id),
                           FOREIGN KEY (creado_por)   REFERENCES usuarios(id) ON DELETE SET NULL,
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
                          estado          ENUM('en_curso','terminada') NOT NULL DEFAULT 'en_curso',
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

SET FOREIGN_KEY_CHECKS = 1;

