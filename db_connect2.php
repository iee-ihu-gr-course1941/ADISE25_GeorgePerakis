<?php
require_once 'db_upass.php';

// Path to MySQL socket
$socket_path = '/home/student/iee/2021/mysql/run/mysql.sock';

try {
    $pdo = new PDO(
        "mysql:dbname=adise25;unix_socket=$socket_path;charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    echo json_encode([
        "error" => "Database connection failed",
        "message" => $e->getMessage()
    ]);
    exit;
}
