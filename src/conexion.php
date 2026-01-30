<?php

$host = "localhost";
$user = "root";
$pass = "admin123";
$dbname="restaurante";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname",$user,$pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión con la base de datos: " . $e -> getMessage());
}