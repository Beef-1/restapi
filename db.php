<?php 
$host = "localhost";
$user = "root";
$password = "";
$dbname = "main";
$adminpass = "admin";

try {
    $pdo = new PDO("mysql:host=$host", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS id_table (
        user TEXT,
        pass TEXT
    )");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `id_table` WHERE `user` = 'adm'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO `id_table` (`user`, `pass`) VALUES ('adm', :pass)");
        $stmt->execute([':pass' => password_hash($adminpass, PASSWORD_DEFAULT)]);
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
