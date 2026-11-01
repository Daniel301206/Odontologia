CREATE DATABASE IF NOT EXISTS odontologia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE odontologia;

CREATE TABLE IF NOT EXISTS RegistroLogin (
    id_Registro INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    Correo VARCHAR(100) NOT NULL UNIQUE,
    Contrasena VARCHAR(255) NOT NULL,
    rol ENUM('admin','recepcion') NOT NULL DEFAULT 'recepcion',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS RegistroCliente (
    id_registroCliente INT PRIMARY KEY AUTO_INCREMENT,
    nombreCompleto VARCHAR(100) NOT NULL,
    DNI CHAR(8) NOT NULL UNIQUE,
    Telefono VARCHAR(15) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS adminisOdontologo (
    id_odontologo INT PRIMARY KEY AUTO_INCREMENT,
    nombreCompleto VARCHAR(100) NOT NULL,
    Telefono VARCHAR(15) NOT NULL,
    especialidad VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS GestionServicios (
    id_GestionServicios INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    tipoServicio VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    CHECK (precio > 0)
);

CREATE TABLE IF NOT EXISTS ProgramarCitas (
    id_Programar INT PRIMARY KEY AUTO_INCREMENT,
    id_registroCliente INT NOT NULL,
    id_odontologo INT NOT NULL,
    id_GestionServicios INT NOT NULL,
    fecha DATETIME NOT NULL,
    estado ENUM('Pendiente','Atendida','Cancelada') NOT NULL DEFAULT 'Pendiente',
    Diagnostico VARCHAR(255) NULL,
    Tratamiento VARCHAR(100) NULL,
    FOREIGN KEY (id_registroCliente) REFERENCES RegistroCliente(id_registroCliente),
    FOREIGN KEY (id_odontologo) REFERENCES adminisOdontologo(id_odontologo),
    FOREIGN KEY (id_GestionServicios) REFERENCES GestionServicios(id_GestionServicios),
    UNIQUE (id_odontologo, fecha),
    INDEX idx_citas_estado_fecha (estado, fecha)
);

CREATE TABLE IF NOT EXISTS TipoPago (
    id_tipoPago INT PRIMARY KEY AUTO_INCREMENT,
    id_Programar INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipoPago ENUM('Efectivo','Tarjeta de crédito','Tarjeta de débito','Yape') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    CHECK (cantidad > 0),
    FOREIGN KEY (id_Programar) REFERENCES ProgramarCitas(id_Programar) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ReportesAtencion (
    id_reportes INT PRIMARY KEY AUTO_INCREMENT,
    id_Programar INT NOT NULL,
    ingreso DECIMAL(10,2),
    tipoPago ENUM('Efectivo','Tarjeta de crédito','Tarjeta de débito','Yape') NOT NULL,
    estado VARCHAR(15) NOT NULL,
    FOREIGN KEY (id_Programar) REFERENCES ProgramarCitas(id_Programar) ON DELETE CASCADE
);