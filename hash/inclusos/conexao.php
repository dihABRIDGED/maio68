<?php 

$host = 'localhost';
$dbname = 'fluxusdb';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Manter compatibilidade com código existente
    $con = $pdo;
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

?>
