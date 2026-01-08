<?php
$db   = 'adise25';
require_once "db_upass.php"; 
$user = $DB_USER;
$pass = $DB_PASS;

if (gethostname() == 'users.iee.ihu.gr') {
    $dsn = "mysql:unix_socket=/home/student/iee/2021/iee2021129/mysql/run/mysql.sock;dbname=$db;charset=utf8";
} else {
    $dsn = "mysql:host=localhost;dbname=$db;charset=utf8";
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to connect to MySQL: ' . $e->getMessage()
    ]);
    exit;
}
