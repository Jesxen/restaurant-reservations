<?php
/**
 * Validación del formulario.
 *
 * @param PDO    $pdo       Conexión a la base de datos del restaurante
 * @param string $nombre    Nombre del cliente.
 * @param string $email     Correo.
 * @param string $fecha     Fecha de la reserva.
 * @param string $hora      Hora de la reserva.
 * @param int    $personas  Número de personas.
 *
 * @return array Posibles errores.
 */
function validarReserva($pdo, $nombre, $email, $fecha, $hora, $personas) {
    $errores = [];

    if (strlen(trim($nombre)) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido.";
    }

    if (empty($fecha)) {
        $errores[] = "Debes seleccionar una fecha.";
    }

    if (empty($hora)) {
        $errores[] = "Debes seleccionar una hora.";
    }

    if ($personas < 1) {
        $errores[] = "El número de personas debe ser al menos 1.";
    }

    // Aqui comprobamos si ya existe una reserva con los datos aportados por el cliente
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas 
                           WHERE nombre = ? AND fecha = ? AND hora = ? AND personas = ?");
    $stmt->execute([$nombre, $fecha, $hora, $personas]);
    $existe = $stmt->fetchColumn();

    if ($existe > 0) {
        $errores[] = "Ya existe una reserva con esos mismos datos. :(";
    }

    return $errores;
}
