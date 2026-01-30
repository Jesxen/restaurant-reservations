CREATE DATABASE restaurante;

use restaurante;

CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    personas INT NOT NULL
);
