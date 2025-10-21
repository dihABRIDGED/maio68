<?php
// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$user_type = $_SESSION['user_type'] ?? 'aluno';
$username = htmlspecialchars($_SESSION['username'] ?? 'Usuário');
$user_email = htmlspecialchars($_SESSION['user_email'] ?? '');

// Detectar página atual para navegação ativa
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/modern.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <!-- Logo -->
            <a href="home.php" class="logo">
                <img src="../images/logo.png" alt="Logo">
                <span>Sistema Educacional</span>
            </a>
            
            <!-- Botão do menu mobile -->
            <button class="mobile-menu-button" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Navegação principal -->
        <nav class="nav">
            <a href="home.php" class="nav-link <?php echo $current_page === 'home.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Início</span>
            </a>
            
            <?php if ($user_type === 'aluno'): ?>
                <a href="faltas.php" class="nav-link <?php echo $current_page === 'faltas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Faltas</span>
                </a>
                <a href="cronograma.php" class="nav-link <?php echo $current_page === 'cronograma.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Cronograma</span>
                </a>
                <a href="atividades.php" class="nav-link <?php echo $current_page === 'atividades.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    <span>Atividades</span>
                </a>
                
            <?php elseif ($user_type === 'professor'): ?>
                <a href="chamada_nova.php" class="nav-link <?php echo $current_page === 'chamada_nova.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Chamada</span>
                </a>
                <a href="agendamento.php" class="nav-link <?php echo $current_page === 'agendamento.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Agendar</span>
                </a>
                <a href="faltas.php" class="nav-link <?php echo $current_page === 'faltas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Faltas</span>
                </a>
                <a href="cronograma.php" class="nav-link <?php echo $current_page === 'cronograma.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Cronograma</span>
                </a>
                
            <?php else: // coordenador ?>
                <a href="usuarios.php" class="nav-link <?php echo $current_page === 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Usuários</span>
                </a>
                <a href="disciplinas.php" class="nav-link <?php echo $current_page === 'disciplinas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Disciplinas</span>
                </a>
                <a href="faltas.php" class="nav-link <?php echo $current_page === 'faltas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Faltas</span>
                </a>
                <a href="cronograma.php" class="nav-link <?php echo $current_page === 'cronograma.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Cronograma</span>
                </a>
                <a href="relatorios.php" class="nav-link <?php echo $current_page === 'relatorios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Relatórios</span>
                </a>
            <?php endif; ?>
            
            <a href="suporte.php" class="nav-link <?php echo $current_page === 'suporte.php' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i>
                <span>Suporte</span>
            </a>
            
            <!-- Dropdown do usuário -->
            <div class="user-dropdown">
                <button class="user-button" onclick="toggleUserDropdown()">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($username); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                
                <div class="user-dropdown-menu" id="userDropdown">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($user_email); ?></div>
                        <div class="user-type"><?php echo ucfirst($user_type); ?></div>
                    </div>
                    <hr>
                    <a href="perfil.php" class="dropdown-item">
                        <i class="fas fa-user-edit"></i>
                        Meu Perfil
                    </a>
                    <a href="configuracoes.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        Configurações
                    </a>
                    <hr>
                    <a href="../includes/logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Sair
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Menu mobile (Sidebar) -->
        <div class="mobile-menu-overlay" onclick="toggleMobileMenu()"></div>
        <div class="mobile-menu" id="mobileMenu">
            <div class="mobile-menu-header">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-type"><?php echo ucfirst($user_type); ?></div>
                    </div>
                </div>
                <button onclick="toggleMobileMenu()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="mobile-nav">
                <a href="home.php" class="mobile-nav-link <?php echo $current_page === 'home.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                Início
            </a>
            
            <?php if ($user_type === 'aluno'): ?>
                <a href="faltas.php" class="mobile-nav-link <?php echo $current_page === 'faltas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    Faltas
                </a>
                <a href="cronograma.php" class="mobile-nav-link <?php echo $current_page === 'cronograma.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    Cronograma
                </a>
                <a href="atividades.php" class="mobile-nav-link <?php echo $current_page === 'atividades.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i>
                    Atividades
                </a>
                
            <?php elseif ($user_type === 'professor'): ?>
                <a href="chamada_nova.php" class="mobile-nav-link <?php echo $current_page === 'chamada_nova.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    Chamada
                </a>
                <a href="agendamento.php" class="mobile-nav-link <?php echo $current_page === 'agendamento.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-plus"></i>
                    Agendar
                </a>
                <a href="faltas.php" class="mobile-nav-link <?php echo $current_page === 'faltas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    Faltas
                </a>
                <a href="cronograma.php" class="mobile-nav-link <?php echo $current_page === 'cronograma.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    Cronograma
                </a>
                
            <?php else: // coordenador ?>
                <a href="usuarios.php" class="mobile-nav-link <?php echo $current_page === 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    Usuários
                </a>
                <a href="disciplinas.php" class="mobile-nav-link <?php echo $current_page === 'disciplinas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    Disciplinas
                </a>
                <a href="faltas.php" class="mobile-nav-link <?php echo $current_page === 'faltas.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    Faltas
                </a>
                <a href="cronograma.php" class="mobile-nav-link <?php echo $current_page === 'cronograma.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    Cronograma
                </a>
                <a href="relatorios.php" class="mobile-nav-link <?php echo $current_page === 'relatorios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    Relatórios
                </a>
            <?php endif; ?>
            
            <a href="suporte.php" class="mobile-nav-link <?php echo $current_page === 'suporte.php' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i>
                Suporte
            </a>
            
            <a href="../includes/logout.php" class="mobile-nav-link logout">
                <i class="fas fa-sign-out-alt"></i>
                Sair
            </a>
</nav>
	        </div>
	    </header>
	    
	    <script>
	        // Toggle menu mobile
	        function toggleMobileMenu() {
	            const mobileMenu = document.getElementById('mobileMenu');
	            const overlay = document.querySelector('.mobile-menu-overlay');
	            
	            mobileMenu.classList.toggle('show');
	            overlay.classList.toggle('show');
	        }
	        
	        // Toggle dropdown do usuário
	        function toggleUserDropdown() {
	            const dropdown = document.getElementById('userDropdown');
	            dropdown.classList.toggle('show');
	        }
	        
	        // Fechar dropdown ao clicar fora
	        document.addEventListener('click', function(event) {
	            const dropdown = document.getElementById('userDropdown');
	            const userButton = document.querySelector('.user-button');
	            
	            if (dropdown && userButton && !userButton.contains(event.target) && !dropdown.contains(event.target)) {
	                dropdown.classList.remove('show');
	            }
	        });
	        
	        // Fechar menu mobile ao clicar em um link
	        document.querySelectorAll('.mobile-nav-link').forEach(link => {
	            link.addEventListener('click', function() {
	                const mobileMenu = document.getElementById('mobileMenu');
	                const overlay = document.querySelector('.mobile-menu-overlay');
	                
	                mobileMenu.classList.remove('show');
	                overlay.classList.remove('show');
	            });
	        });
	    </script>
	</body>
	</html>
