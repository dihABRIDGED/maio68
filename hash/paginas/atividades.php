<?php
/**
 * Sistema de Visualização de Agenda
 * Exibe atividades de forma profissional
 */

session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: ../index.php");
    exit();
}

require_once "../inclusos/conexao.php";

// Classe para gerenciar visualização da agenda
class AgendaViewer {
    private $con;
    private $user_id;
    private $user_type;
    
    public function __construct($connection, $user_id, $user_type) {
        $this->con = $connection;
        $this->user_id = $user_id;
        $this->user_type = $user_type;
    }
    
    // Obter todas as atividades (com filtro para alunos)
    public function obterAtividades() {
        try {
            if ($this->user_type === 'aluno') {
                // Para alunos, mostrar apenas atividades das disciplinas matriculadas
                $stmt = $this->con->prepare("
                    SELECT a.*, d.nome as disciplina_nome, u.nome as professor_nome
                    FROM atividade a 
                    JOIN disciplina d ON a.disciplina_id = d.id 
                    JOIN usuario u ON a.criado_por = u.id
                    WHERE a.disciplina_id IN (
                        SELECT disciplina_id FROM matricula WHERE aluno_id = :aluno_id
                    )
                    ORDER BY a.data_atividade ASC
                ");
                $stmt->bindParam(":aluno_id", $this->user_id);
            } else {
                // Para professores/coordenadores, mostrar todas as atividades
                $stmt = $this->con->prepare("
                    SELECT a.*, d.nome as disciplina_nome, u.nome as professor_nome
                    FROM atividade a 
                    JOIN disciplina d ON a.disciplina_id = d.id 
                    JOIN usuario u ON a.criado_por = u.id
                    ORDER BY a.data_atividade ASC
                ");
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao obter atividades: " . $e->getMessage());
            return [];
        }
    }
    
    // Obter atividades pendentes (próximos 7 dias)
    public function obterAtividadesPendentes() {
        try {
            $data_hoje = date('Y-m-d');
            $data_limite = date('Y-m-d', strtotime('+7 days'));
            
            if ($this->user_type === 'aluno') {
                $stmt = $this->con->prepare("
                    SELECT a.*, d.nome as disciplina_nome, u.nome as professor_nome
                    FROM atividade a 
                    JOIN disciplina d ON a.disciplina_id = d.id 
                    JOIN usuario u ON a.criado_por = u.id
                    WHERE a.data_atividade BETWEEN :data_hoje AND :data_limite
                    AND a.disciplina_id IN (
                        SELECT disciplina_id FROM matricula WHERE aluno_id = :aluno_id
                    )
                    ORDER BY a.data_atividade ASC
                ");
                $stmt->bindParam(":aluno_id", $this->user_id);
            } else {
                $stmt = $this->con->prepare("
                    SELECT a.*, d.nome as disciplina_nome, u.nome as professor_nome
                    FROM atividade a 
                    JOIN disciplina d ON a.disciplina_id = d.id 
                    JOIN usuario u ON a.criado_por = u.id
                    WHERE a.data_atividade BETWEEN :data_hoje AND :data_limite
                    ORDER BY a.data_atividade ASC
                ");
            }
            
            $stmt->bindParam(":data_hoje", $data_hoje);
            $stmt->bindParam(":data_limite", $data_limite);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao obter atividades pendentes: " . $e->getMessage());
            return [];
        }
    }
}

// Inicializar o visualizador
$agenda = new AgendaViewer($con, $_SESSION["user_id"], $_SESSION["user_type"]);

// Obter dados para exibição
$atividades = $agenda->obterAtividades();
$atividades_pendentes = $agenda->obterAtividadesPendentes();

$con = null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda - Fluxus</title>
    <link rel="stylesheet" href="../css/moderno.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Sistema de Tabs */
        .tabs-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            margin-bottom: var(--spacing-6);
            overflow: hidden;
        }
        
        .tabs-header {
            display: flex;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            overflow-x: auto;
        }
        
        .tab-button {
            flex: 1;
            min-width: 140px;
            padding: var(--spacing-4) var(--spacing-5);
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-600);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
            white-space: nowrap;
        }
        
        .tab-button:hover {
            background: var(--gray-100);
            color: var(--gray-800);
        }
        
        .tab-button.active {
            background: var(--white);
            color: var(--primary-red);
            border-bottom-color: var(--primary-red);
        }
        
        .tab-content {
            display: none;
            padding: var(--spacing-6);
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Cards Compactos */
        .compact-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--spacing-4);
            margin-bottom: var(--spacing-6);
        }
        
        @media (min-width: 768px) {
            .compact-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .compact-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-4);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            height: 100%;
        }
        
        .compact-card-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            margin-bottom: var(--spacing-4);
            padding-bottom: var(--spacing-3);
            border-bottom: 2px solid var(--gray-100);
        }
        
        .compact-card-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-red-light);
            color: var(--primary-red);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        .compact-card-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-4);
            margin-bottom: var(--spacing-6);
        }
        
        /* Highlight Card */
        .highlight-card {
            background: linear-gradient(135deg, var(--primary-red), #e74c3c);
            color: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-5);
            margin-bottom: var(--spacing-6);
        }
        
        .highlight-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .highlight-text h3 {
            color: var(--white);
            margin-bottom: var(--spacing-2);
            font-size: var(--font-size-lg);
        }
        
        .highlight-text p {
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
            font-size: var(--font-size-sm);
        }
        
        .highlight-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        /* Tabelas Responsivas */
        .table-responsive {
            overflow-x: auto;
        }
        
        /* Utilitários */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <?php require_once "../inclusos/cabecalho.php"; ?>

    <main class="container" style="padding-top: var(--spacing-8);">
        <!-- Cabeçalho -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">
                <i class="fas fa-calendar-alt text-primary-red mr-3"></i>
                Minha Agenda
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Acompanhe suas atividades de forma organizada e eficiente
            </p>
        </div>

        <!-- Sistema de Tabs -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-button active" data-tab="resumo">
                    <i class="fas fa-chart-bar"></i>
                    Resumo
                </button>
                <button class="tab-button" data-tab="pendentes">
                    <i class="fas fa-exclamation-circle"></i>
                    Pendentes
                </button>
                <button class="tab-button" data-tab="atividades">
                    <i class="fas fa-tasks"></i>
                    Todas as Atividades
                </button>
            </div>

            <!-- Conteúdo das Tabs -->
            <div class="tab-content active" id="resumo-tab">
                <!-- Destaque de Urgência -->
                <?php if (!empty($atividades_pendentes)): ?>
                <div class="highlight-card">
                    <div class="highlight-content">
                        <div class="highlight-text">
                            <h3><i class="fas fa-bell mr-2"></i> Atenção!</h3>
                            <p>Você tem <?php echo count($atividades_pendentes); ?> atividade(s) pendente(s) para esta semana</p>
                        </div>
                        <div class="highlight-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Cards de Estatísticas -->
                <div class="stats-grid">
                    <div class="card metric-card">
                        <div class="card-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="metric-number"><?php echo count($atividades); ?></div>
                        <div class="metric-label">Total de Atividades</div>
                    </div>
                    
                    <div class="card metric-card">
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="metric-number"><?php echo count($atividades_pendentes); ?></div>
                        <div class="metric-label">Pendentes (7 dias)</div>
                    </div>
                </div>

                <!-- Visão Rápida -->
                <div class="compact-grid">
                    <!-- Atividades Pendentes -->
                    <div class="compact-card">
                        <div class="compact-card-header">
                            <div class="compact-card-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <h3 class="compact-card-title">Atividades Pendentes</h3>
                        </div>
                        
                        <div class="space-y-3">
                            <?php if (empty($atividades_pendentes)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle text-success text-2xl mb-2"></i>
                                    <p class="text-gray-500 text-sm">Nenhuma atividade pendente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($atividades_pendentes, 0, 3) as $atividade): ?>
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-start justify-between mb-2">
                                            <h4 class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($atividade['titulo']); ?></h4>
                                            <span class="badge <?php echo $atividade['tipo'] === 'prova' ? 'badge-danger' : 'badge-warning'; ?> text-xs">
                                                <?php echo ucfirst($atividade['tipo']); ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 mb-2 line-clamp-2"><?php echo htmlspecialchars($atividade['descricao']); ?></p>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500">
                                                <?php echo htmlspecialchars($atividade['disciplina_nome']); ?>
                                            </span>
                                            <span class="font-semibold text-primary-red">
                                                <?php echo date('d/m', strtotime($atividade['data_atividade'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($atividades_pendentes) > 3): ?>
                                    <div class="text-center pt-2">
                                        <span class="text-xs text-primary-red font-semibold">
                                            +<?php echo count($atividades_pendentes) - 3; ?> atividades pendentes
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Pendentes -->
            <div class="tab-content" id="pendentes-tab">
                <div class="compact-card">
                    <div class="compact-card-header">
                        <div class="compact-card-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h3 class="compact-card-title">Atividades Pendentes</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($atividades_pendentes)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-check-circle text-success text-4xl mb-4"></i>
                                <p class="text-gray-500">Nenhuma atividade pendente para os próximos dias</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($atividades_pendentes as $atividade): ?>
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-start justify-between mb-2">
                                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($atividade['titulo']); ?></h4>
                                        <span class="badge <?php echo $atividade['tipo'] === 'prova' ? 'badge-danger' : 'badge-warning'; ?>">
                                            <?php echo ucfirst($atividade['tipo']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($atividade['descricao']); ?></p>
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-4">
                                            <span class="flex items-center gap-1 text-gray-500">
                                                <i class="fas fa-book"></i>
                                                <?php echo htmlspecialchars($atividade['disciplina_nome']); ?>
                                            </span>
                                            <span class="flex items-center gap-1 text-gray-500">
                                                <i class="fas fa-user-tie"></i>
                                                <?php echo htmlspecialchars($atividade['professor_nome']); ?>
                                            </span>
                                        </div>
                                        <span class="font-semibold text-primary-red">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            <?php echo date('d/m/Y', strtotime($atividade['data_atividade'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab: Todas as Atividades -->
            <div class="tab-content" id="atividades-tab">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Disciplina</th>
                                <th>Título</th>
                                <th>Tipo</th>
                                <th>Professor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($atividades)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-inbox mr-2"></i>
                                        Nenhuma atividade encontrada
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($atividades as $atividade): 
                                    $data_atividade = strtotime($atividade['data_atividade']);
                                    $hoje = strtotime(date('Y-m-d'));
                                    $status = $data_atividade < $hoje ? 'Atrasada' : 'Pendente';
                                    $status_class = $data_atividade < $hoje ? 'badge-danger' : 'badge-warning';
                                ?>
                                    <tr>
                                        <td class="font-semibold">
                                            <i class="fas fa-calendar-day text-primary-red mr-2"></i>
                                            <?php echo date('d/m/Y', $data_atividade); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($atividade['disciplina_nome']); ?></td>
                                        <td>
                                            <div class="max-w-md">
                                                <div class="font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($atividade['titulo']); ?>
                                                </div>
                                                <div class="text-sm text-gray-600 mt-1">
                                                    <?php echo htmlspecialchars($atividade['descricao']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo ucfirst($atividade['tipo']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($atividade['professor_nome']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sistema de Tabs
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            function switchTab(tabName) {
                // Remove active class de todos os botões e conteúdos
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Adiciona active class ao botão e conteúdo correspondente
                const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
                const activeContent = document.getElementById(`${tabName}-tab`);
                
                if (activeButton && activeContent) {
                    activeButton.classList.add('active');
                    activeContent.classList.add('active');
                    activeContent.classList.add('fade-in');
                }
            }
            
            // Event listeners para os botões das tabs
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    switchTab(tabName);
                });
            });
            
            // Animações de entrada
            const cards = document.querySelectorAll('.card, .compact-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Hover effects para tabelas
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'var(--gray-50)';
                    this.style.transition = 'background-color 0.2s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
            
            // Verificar se há atividades pendentes e destacar a tab
            <?php if (!empty($atividades_pendentes)): ?>
            setTimeout(() => {
                const pendentesTab = document.querySelector('[data-tab="pendentes"]');
                if (pendentesTab) {
                    pendentesTab.innerHTML += ' <span class="badge badge-danger" style="margin-left: 5px;"><?php echo count($atividades_pendentes); ?></span>';
                }
            }, 1000);
            <?php endif; ?>
        });
    </script>
</body>
</html>