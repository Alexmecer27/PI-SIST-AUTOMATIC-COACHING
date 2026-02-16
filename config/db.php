<?php
// config/db.php

// 1. FORZAR HORA PHP (Evita desfases de tiempo)
date_default_timezone_set('America/Guayaquil');

$host = 'localhost';
$db   = 'coaching_sistema_certificados_dbb';
$user = 'coaching_sistema_certificados';
$pass = '~=M(&J]@B{tV.MVq';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 2. FORZAR HORA MYSQL (Sincroniza la BD con PHP)
    $pdo->exec("SET time_zone = '-05:00';");
    
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>