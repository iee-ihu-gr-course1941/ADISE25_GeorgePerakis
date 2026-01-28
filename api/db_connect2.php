<?php
$db = 'adise25';
require_once "db_upass.php";

if (gethostname() === 'users.iee.ihu.gr') {
    $dsn = "mysql:unix_socket=/home/student/iee/2021/iee2021129/mysql/run/mysql.sock;dbname=$db;charset=utf8mb4";
    $user = $DB_USER;
    $pass = $DB_PASS;
} else {
    $dsn = "mysql:host=127.0.0.1;dbname=$db;charset=utf8mb4";
    $user = $DB_USER;
    $pass = $DB_PASS;
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
