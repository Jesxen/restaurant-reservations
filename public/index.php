<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/conexion.php';

use Jenssegers\Blade\Blade;

$views = __DIR__ . '/../views';
$cache = __DIR__ . '/../cache';

$blade = new Blade($views, $cache);

//Cargamos las vistas de nuestra web
$vista = $_GET['vista'] ?? 'reservas';

switch ($vista) {
    case 'listado':
        $reservas = $pdo->query("
            SELECT nombre AS usuario, fecha, hora, personas
            FROM reservas
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo $blade->render('listado', compact('reservas'));
        break;

    case 'mapa':
        echo $blade->render('mapa');
        break;

    default:
        echo $blade->render('reservas');
        break;
}
