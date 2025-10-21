<?php
/**
 * Página Home Moderna - Sistema Educacional
 * Dashboard integrado com banco de dados real
 */

session_start();
require_once '../includes/connection.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$user_type = $_SESSION['user_type'] ?? 'aluno';
$user_id = $_SESSION['user_id'] ?? 0;
$username = htmlspecialchars($_SESSION['username'] ?? 'Usuário');

// Buscar dados do dashboard baseado no tipo de usuário
$dashboard_data = [];
$proximas_aulas = [];
$atividades_recentes = [];

try {
    if ($user_type === 'aluno') {
        // Dados do aluno - usando tabelas reais
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT m.disciplina_id) as total_disciplinas,
                COUNT(CASE WHEN f.presente = 0 THEN 1 END) as total_faltas,
                COUNT(CASE WHEN f.presente = 1 THEN 1 END) as total_presencas,
                COUNT(f.id) as total_chamadas
            FROM matricula m
            LEFT JOIN aula a ON a.turma_id = m.disciplina_id
            LEFT JOIN frequencia f ON f.aula_id = a.id AND f.aluno_id = m.aluno_id
            WHERE m.aluno_id = ?
        ");
        $stmt->execute([$user_id]);
        $dashboard_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Garantir que os valores não sejam null
        $dashboard_data = array_map(function($value) {
            return $value ?? 0;
        }, $dashboard_data);
        
        // Próximas atividades do aluno
        $stmt = $pdo->prepare("
            SELECT 
                at.titulo,
                at.descricao,
                at.data_atividade,
                at.tipo,
                d.nome as disciplina_nome,
                u.nome as professor_nome
            FROM atividade at
            JOIN disciplina d ON at.disciplina_id = d.id
            JOIN usuario u ON d.professor_id = u.id
            JOIN matricula m ON m.disciplina_id = d.id
            WHERE m.aluno_id = ? AND at.data_atividade >= CURDATE()
            ORDER BY at.data_atividade ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $atividades_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Próximas aulas (simuladas - baseadas nas disciplinas matriculadas)
        $stmt = $pdo->prepare("
            SELECT 
                d.nome as disciplina_nome,
                u.nome as professor_nome,
                'Segunda-feira 08:00' as horario_simulado
            FROM matricula m
            JOIN disciplina d ON m.disciplina_id = d.id
            JOIN usuario u ON d.professor_id = u.id
            WHERE m.aluno_id = ?
            LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $proximas_aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($user_type === 'professor') {
        // Dados do professor
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT d.id) as total_disciplinas,
                COUNT(DISTINCT a.id) as total_aulas,
                COUNT(f.id) as total_chamadas,
                COUNT(CASE WHEN f.presente = 0 THEN 1 END) as total_faltas
            FROM disciplina d
            LEFT JOIN aula a ON a.turma_id = d.id
            LEFT JOIN frequencia f ON f.aula_id = a.id
            WHERE d.professor_id = ?
        ");
        $stmt->execute([$user_id]);
        $dashboard_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Garantir que os valores não sejam null
        $dashboard_data = array_map(function($value) {
            return $value ?? 0;
        }, $dashboard_data);
        
        // Atividades criadas pelo professor
        $stmt = $pdo->prepare("
            SELECT 
                at.titulo,
                at.descricao,
                at.data_atividade,
                at.tipo,
                d.nome as disciplina_nome
            FROM atividade at
            JOIN disciplina d ON at.disciplina_id = d.id
            WHERE at.criado_por = ?
            ORDER BY at.criado_em DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $atividades_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Dados do coordenador - visão geral
        $stmt = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM usuario) as total_usuarios,
                (SELECT COUNT(*) FROM usuario WHERE tipo = 'aluno') as total_alunos,
                (SELECT COUNT(*) FROM usuario WHERE tipo = 'professor') as total_professores,
                (SELECT COUNT(*) FROM disciplina) as total_disciplinas
        ");
        $stmt->execute();
        $dashboard_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Atividades recentes do sistema
        $stmt = $pdo->prepare("
            SELECT 
                at.titulo,
                at.descricao,
                at.data_atividade,
                at.tipo,
                d.nome as disciplina_nome,
                u.nome as professor_nome
            FROM atividade at
            JOIN disciplina d ON at.disciplina_id = d.id
            JOIN usuario u ON d.professor_id = u.id
            ORDER BY at.criado_em DESC
            LIMIT 5
        ");
        $stmt->execute();
        $atividades_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    // Em caso de erro, usar dados padrão baseado no tipo de usuário
    if ($user_type === 'aluno') {
        $dashboard_data = [
            'total_disciplinas' => 0,
            'total_faltas' => 0,
            'total_presencas' => 0,
            'total_chamadas' => 0
        ];
    } elseif ($user_type === 'professor') {
        $dashboard_data = [
            'total_disciplinas' => 0,
            'total_aulas' => 0,
            'total_chamadas' => 0,
            'total_faltas' => 0
        ];
    } else {
        $dashboard_data = [
            'total_usuarios' => 0,
            'total_alunos' => 0,
            'total_professores' => 0,
            'total_disciplinas' => 0
        ];
    }
    
    // Inicializar arrays vazios para atividades
    $proximas_aulas = [];
    $atividades_recentes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Educacional</title>
    <link rel="stylesheet" href="../css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            padding: var(--spacing-6);
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-red), #dc2626);
            color: var(--white);
            padding: var(--spacing-8);
            border-radius: var(--border-radius-lg);
            margin-bottom: var(--spacing-8);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        
        .welcome-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-2);
        }
        
        .welcome-subtitle {
            font-size: var(--font-size-lg);
            opacity: 0.9;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }
        
        .metric-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-4);
        }
        
        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xl);
            color: var(--white);
            background: var(--primary-red);
        }
        
        .metric-value {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-1);
        }
        
        .metric-label {
            color: var(--gray-600);
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }
        
        .metric-change {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: var(--font-size-sm);
            margin-top: var(--spacing-2);
        }
        
        .metric-change.positive {
            color: var(--success);
        }
        
        .metric-change.negative {
            color: var(--danger);
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-6);
        }
        
        .chart-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }
        
        .chart-header {
            margin-bottom: var(--spacing-6);
        }
        
        .chart-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-2);
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }
        
        .chart-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--border-radius);
            background: var(--primary-red);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chart-subtitle {
            color: var(--gray-600);
            font-size: var(--font-size-sm);
        }
        
        .activity-list {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-4);
            padding: var(--spacing-4);
            border-radius: var(--border-radius);
            transition: background-color 0.2s ease;
        }
        
        .activity-item:hover {
            background: var(--gray-50);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--border-radius);
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-1);
        }
        
        .activity-meta {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-8);
            color: var(--gray-500);
        }
        
        .empty-state i {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--spacing-4);
            color: var(--gray-400);
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: var(--spacing-4);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-section {
                padding: var(--spacing-6);
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-container">
            <!-- Seção de boas-vindas -->
            <div class="welcome-section">
                <h1 class="welcome-title">
                    Bem-vindo, <?php echo $username; ?>!
                </h1>
                <p class="welcome-subtitle">
                    <?php 
                    if ($user_type === 'aluno') {
                        echo 'Acompanhe seu progresso acadêmico e atividades pendentes.';
                    } elseif ($user_type === 'professor') {
                        echo 'Gerencie suas disciplinas e acompanhe o desempenho dos alunos.';
                    } else {
                        echo 'Visão geral do sistema educacional e relatórios gerenciais.';
                    }
                    ?>
                </p>
            </div>
            
            <!-- Métricas principais -->
            <div class="metrics-grid">
                <?php if ($user_type === 'aluno'): ?>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_disciplinas']; ?></div>
                        <div class="metric-label">Disciplinas Cursadas</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_presencas']; ?></div>
                        <div class="metric-label">Presenças</div>
                        <?php 
                        $total_chamadas = max(1, $dashboard_data['total_chamadas']);
                        $total_presencas = $dashboard_data['total_presencas'];
                        $percentual_presencas = round(($total_presencas / $total_chamadas) * 100, 1);
                        ?>
                        <div class="metric-change <?php echo $percentual_presencas >= 75 ? 'positive' : 'negative'; ?>">
                            <i class="fas fa-percentage"></i>
                            <?php echo $percentual_presencas; ?>% de frequência
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_faltas']; ?></div>
                        <div class="metric-label">Faltas</div>
                        <?php 
                        $total_faltas = $dashboard_data['total_faltas'];
                        $percentual_faltas = $total_chamadas > 0 ? ($total_faltas / $total_chamadas * 100) : 0;
                        ?>
                        <div class="metric-change <?php echo $percentual_faltas > 25 ? 'negative' : 'positive'; ?>">
                            <i class="fas fa-percentage"></i>
                            <?php echo number_format($percentual_faltas, 1); ?>% do total
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_chamadas']; ?></div>
                        <div class="metric-label">Total de Chamadas</div>
                    </div>
                    
                <?php elseif ($user_type === 'professor'): ?>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_disciplinas']; ?></div>
                        <div class="metric-label">Disciplinas</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_aulas']; ?></div>
                        <div class="metric-label">Aulas Ministradas</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_chamadas']; ?></div>
                        <div class="metric-label">Chamadas Realizadas</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-user-times"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_faltas']; ?></div>
                        <div class="metric-label">Faltas Registradas</div>
                    </div>
                    
                <?php else: ?>
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_usuarios']; ?></div>
                        <div class="metric-label">Total de Usuários</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_alunos']; ?></div>
                        <div class="metric-label">Alunos</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_professores']; ?></div>
                        <div class="metric-label">Professores</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-header">
                            <div class="metric-icon">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <div class="metric-value"><?php echo $dashboard_data['total_disciplinas']; ?></div>
                        <div class="metric-label">Disciplinas</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Gráficos e atividades -->
            <div class="content-grid">
                <div class="chart-section">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <div class="chart-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <?php 
                            if ($user_type === 'aluno') {
                                echo 'Frequência Geral';
                            } elseif ($user_type === 'professor') {
                                echo 'Atividades por Tipo';
                            } else {
                                echo 'Usuários por Tipo';
                            }
                            ?>
                        </h3>
                        <p class="chart-subtitle">Dados dos últimos 30 dias</p>
                    </div>
                    <div style="height: 250px; position: relative;">
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>
                
                <div class="activity-list">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <div class="chart-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <?php echo $user_type === 'aluno' ? 'Próximas Atividades' : 'Atividades Recentes'; ?>
                        </h3>
                    </div>
                    
                    <?php if (!empty($atividades_recentes)): ?>
                        <?php foreach ($atividades_recentes as $atividade): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php 
                                        $icon = 'tasks';
                                        switch ($atividade['tipo']) {
                                            case 'prova':
                                                $icon = 'file-alt';
                                                break;
                                            case 'trabalho':
                                                $icon = 'users';
                                                break;
                                            case 'exercicio':
                                                $icon = 'pencil-alt';
                                                break;
                                            case 'redacao':
                                                $icon = 'pen';
                                                break;
                                            case 'relatorio':
                                                $icon = 'chart-line';
                                                break;
                                        }
                                        echo $icon;
                                    ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($atividade['titulo']); ?></div>
                                    <div class="activity-meta">
                                        <?php echo htmlspecialchars($atividade['disciplina_nome']); ?> • 
                                        <?php echo date('d/m/Y', strtotime($atividade['data_atividade'])); ?>
                                        <?php if (isset($atividade['professor_nome'])): ?>
                                            • <?php echo htmlspecialchars($atividade['professor_nome']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <h3>Nenhuma atividade</h3>
                            <p>Não há atividades pendentes no momento.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Configurar gráfico baseado no tipo de usuário
        const ctx = document.getElementById('mainChart').getContext('2d');
        
        <?php if ($user_type === 'aluno'): ?>
            // Gráfico de frequência para aluno
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Presenças', 'Faltas'],
                    datasets: [{
                        data: [<?php echo $dashboard_data['total_presencas']; ?>, <?php echo $dashboard_data['total_faltas']; ?>],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderWidth: 0,
                        cutout: '60%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        <?php elseif ($user_type === 'professor'): ?>
            // Gráfico de atividades para professor
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Aulas', 'Disciplinas', 'Chamadas'],
                    datasets: [{
                        label: 'Quantidade',
                        data: [<?php echo $dashboard_data['total_aulas']; ?>, <?php echo $dashboard_data['total_disciplinas']; ?>, <?php echo $dashboard_data['total_chamadas']; ?>],
                        backgroundColor: '#dc2626',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php else: ?>
            // Gráfico de usuários para coordenador
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Alunos', 'Professores'],
                    datasets: [{
                        data: [<?php echo $dashboard_data['total_alunos']; ?>, <?php echo $dashboard_data['total_professores']; ?>],
                        backgroundColor: ['#3b82f6', '#dc2626'],
                        borderWidth: 0,
                        cutout: '60%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
        
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.metric-card, .chart-section, .activity-list');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.5s ease-out';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
