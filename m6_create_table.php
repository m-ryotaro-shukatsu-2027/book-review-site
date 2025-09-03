<?php
$dsn = 'mysql:dbname=データベース名;host=localhost';
$user = 'ユーザー名';
$password = 'パスワード';
$pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

$sql = "CREATE TABLE IF NOT EXISTS mission6_1"
        ." ("
        . "id INT AUTO_INCREMENT PRIMARY KEY,"
        . "name CHAR(32),"
        . "comment TEXT,"
        . "created_at DATETIME,"
        . "password VARCHAR(255),"
        . "image VARCHAR(255)"
        .");";
 
$stmt = $pdo->query($sql);
?>