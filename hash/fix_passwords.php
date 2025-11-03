<?php
require_once 'inclusos/conexao.php';

try {
    $stmt = $pdo->query("SELECT id, senha FROM usuario");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $hashedPassword = password_hash($user['senha'], PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE usuario SET senha = ? WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $user['id']]);
    }

    echo "Senhas atualizadas com sucesso!";
} catch (PDOException $e) {
    echo "Erro ao atualizar senhas: " . $e->getMessage();
}
?>
