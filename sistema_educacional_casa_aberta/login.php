<?php
/**
 * Página de Login - Sistema Educacional Moderno
 * Design corporativo profissional
 */

session_start();

// Se já estiver logado, redireciona para home
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: pages/home.php');
    exit();
}

// Processar login
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/connection.php';
    
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($login) && !empty($senha)) {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, tipo, senha FROM usuario WHERE login = ? AND ativo = 1");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $senha === $user['senha']) { // Em produção, usar password_verify()
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['tipo'];
                
                header('Location: pages/home.php');
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
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos específicos para a página de login */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            padding: var(--spacing-6);
        }
        
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--white);
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--spacing-10);
            text-align: center;
        }
        
        .login-logo {
            margin-bottom: var(--spacing-8);
        }
        
        .login-logo img {
            height: 60px;
            width: auto;
            margin-bottom: var(--spacing-4);
        }
        
        .login-title {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-2);
        }
        
        .login-subtitle {
            color: var(--gray-600);
            margin-bottom: var(--spacing-8);
        }
        
        .login-form {
            text-align: left;
        }
        
        .form-group {
            position: relative;
            margin-bottom: var(--spacing-6);
        }
        
        .form-icon {
            position: absolute;
            left: var(--spacing-4);
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: var(--font-size-lg);
        }
        
        .form-input-with-icon {
            padding-left: var(--spacing-12);
        }
        
        .login-button {
            width: 100%;
            padding: var(--spacing-4);
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-6);
        }
        
        .demo-credentials {
            background: var(--gray-50);
            border-radius: var(--border-radius);
            padding: var(--spacing-4);
            margin-top: var(--spacing-6);
            text-align: left;
        }
        
        .demo-credentials h4 {
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--spacing-3);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .demo-user {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-2) 0;
            border-bottom: 1px solid var(--gray-200);
            font-size: var(--font-size-sm);
        }
        
        .demo-user:last-child {
            border-bottom: none;
        }
        
        .demo-user-type {
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .demo-user-login {
            color: var(--gray-500);
            font-family: monospace;
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--border-radius);
            margin-bottom: var(--spacing-6);
            font-size: var(--font-size-sm);
            border-left: 4px solid var(--danger);
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: var(--spacing-4);
            }
            
            .login-card {
                padding: var(--spacing-6);
            }
        }
    </style>
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
        // Adicionar animação de entrada
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
        
        // Auto-focus no primeiro campo
        document.getElementById('login').focus();
    </script>
</body>
</html>
