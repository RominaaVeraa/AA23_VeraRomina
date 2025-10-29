
CREATE DATABASE IF NOT EXISTS halloween
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE halloween;

DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  clave TEXT NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS disfraces;
CREATE TABLE disfraces (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  descripcion TEXT NOT NULL,
  votos INT(11) NOT NULL DEFAULT 0,
  foto VARCHAR(20) NOT NULL,
  foto_blob BLOB NOT NULL,
  eliminado INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS votos;
CREATE TABLE votos (
  id INT(11) NOT NULL AUTO_INCREMENT,
  id_usuario INT(11) NOT NULL,
  id_disfraz INT(11) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_voto (id_usuario, id_disfraz),
  CONSTRAINT fk_voto_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_voto_disfraz FOREIGN KEY (id_disfraz) REFERENCES disfraces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO disfraces (nombre, descripcion, votos, foto, foto_blob, eliminado) VALUES
('La Calabaza Misteriosa', 'Disfraz clásico con antifaz y capa naranja.', 0, 'imagen1.jpg', '', 0),
('Vampiro Elegante', 'Smokin, capa y lentes rojos. Muy retro.', 0, 'imagen2.jpg', '', 0),
('Bruja Urbana', 'Sombrero y escoba, estilo urbano.', 0, 'imagen3.jpg', '', 0);
