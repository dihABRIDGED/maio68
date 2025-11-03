<?php


session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: paginas/inicio.php');
    exit();
}

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'inclusos/conexao.php';
    
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($login) && !empty($senha)) {
        try {
           
            $stmt = $pdo->prepare("SELECT id, nome, email, tipo, senha FROM usuario WHERE login = ? AND ativo = 1");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['tipo'];
                
                header('Location: paginas/inicio.php');
                exit();
            } else {
                $error_message = 'Credenciais inválidas. Verifique seu login e senha.';
            }
        } catch (PDOException $e) {
            $error_message = 'Erro no sistema. Tente novamente mais tarde.';
        }
    } else {
        $error_message = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Educacional</title>
    <link rel="stylesheet" href="css/moderno.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-logo">
                <img src="images/logo.png" alt="Logo Sistema Educacional">
                <h1 class="login-title">Sistema Educacional</h1>
                <p class="login-subtitle">Faça login para acessar sua conta</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="login" class="form-label">Login</label>
                    <div style="position: relative;">
                        <i class="fas fa-user form-icon"></i>
                        <input 
                            type="text" 
                            id="login" 
                            name="login" 
                            class="form-input form-input-with-icon" 
                            placeholder="Digite seu login"
                            value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                    <div style="position: relative;">
                        <i class="fas fa-lock form-icon"></i>
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            class="form-input form-input-with-icon" 
                            placeholder="Digite sua senha"
                            required
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary login-button">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>
            </form>
            
            <div class="demo-credentials">
                <h4>Credenciais de Demonstração</h4>
                <div class="demo-user">
                    <span class="demo-user-type">Coordenador:</span>
                    <span class="demo-user-login">ana.souza@fluxus.edu</span>
                </div>
                <div class="demo-user">
                    <span class="demo-user-type">Professor:</span>
                    <span class="demo-user-login">carla.ribeiro@fluxus.edu</span>
                </div>
                <div class="demo-user">
                    <span class="demo-user-type">Aluno:</span>
                    <span class="demo-user-login">rodrigo.silva@estudante.fluxus.edu</span>
                </div>
                <div style="margin-top: var(--spacing-3); padding-top: var(--spacing-3); border-top: 1px solid var(--gray-200); text-align: center;">
                    <small class="text-gray-500">Senha para todos: <strong>123456</strong></small>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
        
        
        document.getElementById('login').focus();
    </script>
</body>
</html>
