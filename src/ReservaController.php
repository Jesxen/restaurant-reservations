<?php
require __DIR__ . '/conexion.php';
require __DIR__ . '/validarReserva.php';

$nombre   = $_POST['nombre'] ?? '';
$email    = $_POST['email'] ?? '';
$fecha    = $_POST['fecha'] ?? '';
$hora     = $_POST['hora'] ?? '';
$personas = $_POST['personas'] ?? 0;

//Validamos si existen reservas repetidas
$errores = validarReserva($pdo, $nombre, $email, $fecha, $hora, $personas);

if (!empty($errores)) {
    echo implode("<br>", $errores);
    exit;
}


$stmt = $pdo->prepare("INSERT INTO reservas (nombre, email, fecha, hora, personas) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$nombre, $email, $fecha, $hora, $personas]);

echo "¡Reserva confirmada!";
