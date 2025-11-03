<?php
/**
 * Página Home Moderna - Sistema Educacional
 * Dashboard integrado com banco de dados real
 */

session_start();
require_once '../inclusos/conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

$user_type = $_SESSION['user_type'] ?? 'aluno';
$user_id = $_SESSION['user_id'] ?? 0;
$username = htmlspecialchars($_SESSION['username'] ?? 'Usuário');

// DEBUG: Verificar dados da sessão
error_log("Dashboard - User ID: $user_id, User Type: $user_type, Username: $username");

// Buscar dados do dashboard baseado no tipo de usuário
$dashboard_data = [];
$proximas_aulas = [];
$atividades_recentes = [];

try {
    // DEBUG: Verificar conexão
    error_log("Tentando conectar ao banco...");
    
    if ($user_type === 'aluno') {
        error_log("Buscando dados para ALUNO ID: $user_id");
        
        // Dados do aluno - consulta simplificada e corrigida
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT m.disciplina_id) as total_disciplinas
            FROM matricula m 
            WHERE m.aluno_id = ?
        ");
        $stmt->execute([$user_id]);
        $disciplinas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Contar frequências separadamente
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_chamadas,
                SUM(CASE WHEN f.presente = 1 THEN 1 ELSE 0 END) as total_presencas,
                SUM(CASE WHEN f.presente = 0 THEN 1 ELSE 0 END) as total_faltas
            FROM frequencia f
            JOIN aula a ON f.aula_id = a.id
            JOIN matricula m ON m.disciplina_id = a.disciplina_id AND m.aluno_id = f.aluno_id
            WHERE f.aluno_id = ?
        ");
        $stmt->execute([$user_id]);
        $frequencia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Combinar dados
        $dashboard_data = [
            'total_disciplinas' => $disciplinas['total_disciplinas'] ?? 0,
            'total_presencas' => $frequencia['total_presencas'] ?? 0,
            'total_faltas' => $frequencia['total_faltas'] ?? 0,
            'total_chamadas' => $frequencia['total_chamadas'] ?? 0
        ];
        
        error_log("Dados aluno: " . print_r($dashboard_data, true));
        
        // Próximas atividades do aluno
        $stmt = $pdo->prepare("
            SELECT 
                a.titulo,
                a.descricao,
                a.data_atividade,
                a.tipo,
                d.nome as disciplina_nome,
                u.nome as professor_nome
            FROM atividade a
            JOIN disciplina d ON a.disciplina_id = d.id
            JOIN usuario u ON a.criado_por = u.id
            JOIN matricula m ON m.disciplina_id = d.id
            WHERE m.aluno_id = ? AND a.data_atividade >= CURDATE()
            ORDER BY a.data_atividade ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $atividades_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Próximas aulas do cronograma
        $stmt = $pdo->prepare("
            SELECT 
                d.nome as disciplina_nome,
                u.nome as professor_nome,
                c.dia_semana,
                c.horario
            FROM cronograma_semanal c
            JOIN disciplina d ON c.disciplina_id = d.id
            JOIN usuario u ON c.professor_id = u.id
            JOIN matricula m ON m.disciplina_id = d.id
            WHERE m.aluno_id = ?
            ORDER BY 
                FIELD(c.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta'),
                c.horario
            LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $proximas_aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($user_type === 'professor') {
        error_log("Buscando dados para PROFESSOR ID: $user_id");
        
        // Dados do professor - consultas separadas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_disciplinas FROM disciplina WHERE professor_id = ?");
        $stmt->execute([$user_id]);
        $disciplinas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_aulas FROM aula WHERE professor_id = ?");
        $stmt->execute([$user_id]);
        $aulas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_chamadas 
            FROM frequencia f 
            JOIN aula a ON f.aula_id = a.id 
            WHERE a.professor_id = ?
        ");
        $stmt->execute([$user_id]);
        $chamadas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_faltas 
            FROM frequencia f 
            JOIN aula a ON f.aula_id = a.id 
            WHERE a.professor_id = ? AND f.presente = 0
        ");
        $stmt->execute([$user_id]);
        $faltas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $dashboard_data = [
            'total_disciplinas' => $disciplinas['total_disciplinas'] ?? 0,
            'total_aulas' => $aulas['total_aulas'] ?? 0,
            'total_chamadas' => $chamadas['total_chamadas'] ?? 0,
            'total_faltas' => $faltas['total_faltas'] ?? 0
        ];
        
        error_log("Dados professor: " . print_r($dashboard_data, true));
        
        // Atividades criadas pelo professor
        $stmt = $pdo->prepare("
            SELECT 
                a.titulo,
                a.descricao,
                a.data_atividade,
                a.tipo,
                d.nome as disciplina_nome
            FROM atividade a
            JOIN disciplina d ON a.disciplina_id = d.id
            WHERE a.criado_por = ?
            ORDER BY a.criado_em DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $atividades_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        error_log("Buscando dados para COORDENADOR");
        
        // Dados do coordenador - consultas simples
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_usuarios FROM usuario WHERE ativo = 1");
        $stmt->execute();
        $usuarios = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_alunos FROM usuario WHERE tipo = 'aluno' AND ativo = 1");
        $stmt->execute();
        $alunos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_professores FROM usuario WHERE tipo = 'professor' AND ativo = 1");
        $stmt->execute();
        $professores = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_disciplinas FROM disciplina WHERE ativo = 1");
        $stmt->execute();
        $disciplinas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $dashboard_data = [
            'total_usuarios' => $usuarios['total_usuarios'] ?? 0,
            'total_alunos' => $alunos['total_alunos'] ?? 0,
            'total_professores' => $professores['total_professores'] ?? 0,
            'total_disciplinas' => $disciplinas['total_disciplinas'] ?? 0
        ];
        
        error_log("Dados coordenador: " . print_r($dashboard_data, true));
        
        // Atividades recentes do sistema
        $stmt = $pdo->prepare("
            SELECT 
                a.titulo,
                a.descricao,
                a.data_atividade,
                a.tipo,
                d.nome as disciplina_nome,
                u.nome as professor_nome
            FROM atividade a
            JOIN disciplina d ON a.disciplina_id = d.id
            JOIN usuario u ON a.criado_por = u.id
            ORDER BY a.criado_em DESC
            LIMIT 5
        ");
        $stmt->execute();
        $atividades_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("ERRO NO DASHBOARD: " . $e->getMessage());
    
    // Dados padrão em caso de erro
    if ($user_type === 'aluno') {
        $dashboard_data = ['total_disciplinas' => 0, 'total_faltas' => 0, 'total_presencas' => 0, 'total_chamadas' => 0];
    } elseif ($user_type === 'professor') {
        $dashboard_data = ['total_disciplinas' => 0, 'total_aulas' => 0, 'total_chamadas' => 0, 'total_faltas' => 0];
    } else {
        $dashboard_data = ['total_usuarios' => 0, 'total_alunos' => 0, 'total_professores' => 0, 'total_disciplinas' => 0];
    }
    
    $proximas_aulas = [];
    $atividades_recentes = [];
}

// Garantir que todos os valores sejam números
$dashboard_data = array_map(function($value) {
    return is_numeric($value) ? $value : 0;
}, $dashboard_data);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Educacional</title>
    <link rel="stylesheet" href="../css/moderno.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../inclusos/cabecalho.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-container">
            <!-- Debug info (remover em produção) -->
           
            
            <!-- Seção de boas-vindas -->
            <div class="welcome-section">
                <h1 class="welcome-title">
                    Bem-vindo, <?php echo $username; ?>!
                </h1>
                <p class="welcome-subtitle" color="white">
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
                                            default:
                                                $icon = 'tasks';
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
    </script>
</body>
</html>