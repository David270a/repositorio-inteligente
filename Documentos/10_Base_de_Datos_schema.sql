-- =========================================================
-- Repositorio Inteligente de Documentos con IA
-- Script de creación de base de datos
-- Importar desde phpMyAdmin en una base de datos vacía llamada:
--   repositorio_inteligente
-- =========================================================

SET NAMES utf8mb4;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'usuario') NOT NULL DEFAULT 'usuario',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carpetas (
    id_carpeta INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    id_carpeta_padre INT NULL,
    id_usuario INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_carpeta_padre) REFERENCES carpetas(id_carpeta) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE documentos (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    id_carpeta INT NOT NULL,
    id_usuario INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    tipo ENUM('pdf', 'docx', 'txt') NOT NULL,
    ruta_almacenamiento VARCHAR(255) NOT NULL,
    texto_extraido MEDIUMTEXT,
    categoria VARCHAR(50) DEFAULT NULL,
    resumen TEXT,
    fecha_carga DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_carpeta) REFERENCES carpetas(id_carpeta) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FULLTEXT KEY ft_texto_extraido (texto_extraido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE metadatos_extraidos (
    id_metadato INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT NOT NULL,
    campo VARCHAR(100) NOT NULL,
    valor VARCHAR(500),
    FOREIGN KEY (id_documento) REFERENCES documentos(id_documento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE embeddings (
    id_embedding INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT NOT NULL,
    fragmento_texto MEDIUMTEXT NOT NULL,
    vector MEDIUMTEXT NOT NULL COMMENT 'Vector de embedding serializado en JSON',
    FOREIGN KEY (id_documento) REFERENCES documentos(id_documento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE procesamiento_log (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT NOT NULL,
    estado ENUM('pendiente', 'procesando', 'completado', 'error') NOT NULL,
    mensaje_error TEXT DEFAULT NULL,
    fecha_evento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_documento) REFERENCES documentos(id_documento) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consultas_log (
    id_consulta INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    pregunta TEXT NOT NULL,
    respuesta MEDIUMTEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario administrador de prueba: correo admin@demo.com / password: Admin1234
-- (hash bcrypt válido, verificable con password_verify() en PHP)
INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES
('Administrador', 'admin@demo.com', '$2b$10$VpHInjFNeEyqYL5U9lYhdOlBME5Eal7egWeR3o/dYRPuRan59Ljz2', 'administrador');

INSERT INTO carpetas (nombre, id_carpeta_padre, id_usuario) VALUES
('Contratos', NULL, 1),
('Facturas', NULL, 1),
('Informes', NULL, 1);
