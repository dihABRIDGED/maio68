<?php
/**
 * Authentication handler - Integração Fluxus + TCC 2.0
 * Funcionalidades do Fluxus com validações do TCC 2.0
 */

require_once '../includes/connection.php';

// Start session for better security
session_start();

// Validate POST data
if (!isset($_POST['login']) || !isset($_POST['senha']) || !isset($_POST['tipoUsuario'])) {
    header('Location: ../public/index.php?error=missing_data');
    exit();
}

$login = $_POST['login'];
$senha = $_POST['senha'];
$tipoUsuario = $_POST['tipoUsuario'];

// Use prepared statements for better security
$stmt = $con->prepare("SELECT * FROM Usuario WHERE login = :login AND senha = :senha AND tipo = :tipo");
$stmt->bindParam(':login', $login);
$stmt->bindParam(':senha', $senha);
$stmt->bindParam(':tipo', $tipoUsuario);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Store user data in session
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_login"] = $user["login"];
    $_SESSION["user_type"] = $user["tipo"];
    $_SESSION['logged_in'] = true;
    
    echo "<script language='javascript' type='text/javascript'>
        alert('Login realizado com sucesso! Bem-vindo(a), " . $user["login"] . "');
        window.location.href='../public/home_integrated.php';
    </script>";
} else {
    echo "<script language='javascript' type='text/javascript'>
        alert('Usuário inexistente, senha incorreta ou tipo de usuário inválido');
        window.location.href='../public/index.php';
    </script>";
}

$con = null;
?>

