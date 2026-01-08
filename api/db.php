<?php
$host = '127.0.0.1';  
$port = 3306;         
$db   = 'gamedb';  
$user = 'root';       
$pass = '1234';       

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die(json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]));
}
