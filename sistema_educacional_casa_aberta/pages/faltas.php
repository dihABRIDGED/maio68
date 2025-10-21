<?php
/**
 * Página de Faltas Moderna - Sistema Educacional
 * Integrada com banco de dados real (frequencia, aula, matricula, disciplina)
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

// Buscar dados de faltas do banco usando tabelas reais
$faltas_data = [];
$estatisticas = [];
$estatisticas_disciplinas = [];

try {
    if ($user_type === 'aluno') {
        // Buscar todas as aulas e frequência do aluno
        $stmt = $pdo->prepare("
            SELECT 
                a.id as aula_id,
                a.data,
                a.conteudo,
                d.nome as disciplina_nome,
                u.nome as professor_nome,
                COALESCE(f.presente, 0) as presente
            FROM matricula m
            JOIN disciplina d ON m.disciplina_id = d.id
            JOIN aula a ON a.turma_id = d.id
            JOIN usuario u ON d.professor_id = u.id
            LEFT JOIN frequencia f ON f.aula_id = a.id AND f.aluno_id = m.aluno_id
            WHERE m.aluno_id = ?
            ORDER BY a.data DESC
        ");
        $stmt->execute([$user_id]);
        $faltas_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular estatísticas gerais
        $total_aulas = count($faltas_data);
        $total_presencas = count(array_filter($faltas_data, function($item) { return $item['presente'] == 1; }));
        $total_faltas = count(array_filter($faltas_data, function($item) { return $item['presente'] == 0; }));
        
        $estatisticas = [
            'total_chamadas' => $total_aulas,
            'total_presencas' => $total_presencas,
            'total_faltas' => $total_faltas,
            'faltas_justificadas' => 0 // Não temos campo de justificativa na estrutura atual
        ];
        
        // Estatísticas por disciplina
        $disciplinas_stats = [];
        foreach ($faltas_data as $aula) {
            $disciplina = $aula['disciplina_nome'];
            if (!isset($disciplinas_stats[$disciplina])) {
                $disciplinas_stats[$disciplina] = ['total' => 0, 'presencas' => 0, 'faltas' => 0];
            }
            $disciplinas_stats[$disciplina]['total']++;
            if ($aula['presente'] == 1) {
                $disciplinas_stats[$disciplina]['presencas']++;
            } else {
                $disciplinas_stats[$disciplina]['faltas']++;
            }
        }
        
        // Converter para formato esperado
        foreach ($disciplinas_stats as $disciplina => $stats) {
            $frequencia = $stats['total'] > 0 ? round(($stats['presencas'] / $stats['total']) * 100, 1) : 0;
            $estatisticas_disciplinas[] = [
                'disciplina' => $disciplina,
                'total_aulas' => $stats['total'],
                'presencas' => $stats['presencas'],
                'faltas' => $stats['faltas'],
                'frequencia' => $frequencia
            ];
        }
        
    } elseif ($user_type === 'professor') {
        // Buscar faltas das disciplinas do professor
        $stmt = $pdo->prepare("
            SELECT 
                a.id as aula_id,
                a.data,
                a.conteudo,
                d.nome as disciplina_nome,
                u.nome as professor_nome,
                ua.nome as aluno_nome,
                COALESCE(f.presente, 0) as presente
            FROM disciplina d
            JOIN aula a ON a.turma_id = d.id
            JOIN usuario u ON d.professor_id = u.id
            LEFT JOIN frequencia f ON f.aula_id = a.id
            LEFT JOIN usuario ua ON f.aluno_id = ua.id
            WHERE d.professor_id = ?
            ORDER BY a.data DESC
        ");
        $stmt->execute([$user_id]);
        $faltas_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filtrar apenas registros com alunos (que têm frequência registrada)
        $faltas_data = array_filter($faltas_data, function($item) {
            return !empty($item['aluno_nome']);
        });
        
    } else {
        // Coordenador - ver todas as faltas do sistema
        $stmt = $pdo->prepare("
            SELECT 
                a.id as aula_id,
                a.data,
                a.conteudo,
                d.nome as disciplina_nome,
                u.nome as professor_nome,
                ua.nome as aluno_nome,
                COALESCE(f.presente, 0) as presente
            FROM aula a
            JOIN disciplina d ON a.turma_id = d.id
            JOIN usuario u ON d.professor_id = u.id
            LEFT JOIN frequencia f ON f.aula_id = a.id
            LEFT JOIN usuario ua ON f.aluno_id = ua.id
            ORDER BY a.data DESC
            LIMIT 100
        ");
        $stmt->execute();
        $faltas_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filtrar apenas registros com alunos
        $faltas_data = array_filter($faltas_data, function($item) {
            return !empty($item['aluno_nome']);
        });
    }
    
} catch (PDOException $e) {
    // Em caso de erro, inicializar arrays vazios
    $faltas_data = [];
    $estatisticas = [
        'total_chamadas' => 0,
        'total_presencas' => 0,
        'total_faltas' => 0,
        'faltas_justificadas' => 0
    ];
    $estatisticas_disciplinas = [];
}

// Calcular frequência geral
$total_chamadas = max(1, $estatisticas['total_chamadas'] ?? 1);
$total_presencas = $estatisticas['total_presencas'] ?? 0;
$frequencia_geral = round(($total_presencas / $total_chamadas) * 100, 1);

// Determinar status da frequência
$status_frequencia = 'good';
if ($frequencia_geral < 75) $status_frequencia = 'critical';
elseif ($frequencia_geral < 85) $status_frequencia = 'warning';
elseif ($frequencia_geral >= 95) $status_frequencia = 'excellent';

// Filtrar apenas faltas para exibição na tabela
$apenas_faltas = array_filter($faltas_data, function($item) {
    return $item['presente'] == 0;
});
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faltas - Sistema Educacional</title>
    <link rel="stylesheet" href="../css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .faltas-container {
            padding: var(--spacing-6);
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: var(--spacing-8);
        }
        
        .page-title {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-2);
        }
        
        .page-subtitle {
            color: var(--gray-600);
            font-size: var(--font-size-lg);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }
        
        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.excellent::before { background: var(--success); }
        .stat-card.good::before { background: var(--info); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.critical::before { background: var(--danger); }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-4);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xl);
            color: var(--white);
        }
        
        .stat-icon.excellent { background: var(--success); }
        .stat-icon.good { background: var(--info); }
        .stat-icon.warning { background: var(--warning); }
        .stat-icon.critical { background: var(--danger); }
        
        .stat-value {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--spacing-1);
        }
        
        .stat-label {
            color: var(--gray-600);
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }
        
        .chart-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }
        
        .section-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--spacing-6);
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
        }
        
        .section-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--border-radius);
            background: var(--primary-red);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .table-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
            grid-column: 1 / -1;
        }
        
        .filters {
            display: flex;
            gap: var(--spacing-4);
            margin-bottom: var(--spacing-6);
            flex-wrap: wrap;
        }
        
        .filter-select {
            min-width: 150px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-4);
        }
        
        .data-table th {
            background: var(--gray-50);
            color: var(--gray-700);
            font-weight: 600;
            padding: var(--spacing-4);
            text-align: left;
            border-bottom: 2px solid var(--gray-200);
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .data-table td {
            padding: var(--spacing-4);
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }
        
        .data-table tr:hover {
            background: var(--gray-50);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            padding: var(--spacing-1) var(--spacing-3);
            border-radius: var(--border-radius-full);
            font-size: var(--font-size-xs);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-badge.falta {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        .status-badge.presente {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-12);
            color: var(--gray-500);
        }
        
        .empty-state i {
            font-size: var(--font-size-4xl);
            margin-bottom: var(--spacing-4);
            color: var(--gray-400);
        }
        
        @media (max-width: 768px) {
            .faltas-container {
                padding: var(--spacing-4);
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .data-table {
                font-size: var(--font-size-sm);
            }
            
            .data-table th,
            .data-table td {
                padding: var(--spacing-2);
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="faltas-container">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-chart-pie"></i>
                    Controle de Faltas
                </h1>
                <p class="page-subtitle">
                    <?php if ($user_type === 'aluno'): ?>
                        Acompanhe sua frequência e histórico de faltas
                    <?php else: ?>
                        Visualize o controle de frequência dos alunos
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Estatísticas principais -->
            <?php if ($user_type === 'aluno'): ?>
            <div class="stats-grid">
                <div class="stat-card <?php echo $status_frequencia; ?>">
                    <div class="stat-header">
                        <div class="stat-icon <?php echo $status_frequencia; ?>">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $frequencia_geral; ?>%</div>
                    <div class="stat-label">Frequência Geral</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon good">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $estatisticas['total_presencas']; ?></div>
                    <div class="stat-label">Presenças</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon warning">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $estatisticas['total_faltas']; ?></div>
                    <div class="stat-label">Total de Faltas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon critical">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $estatisticas['total_chamadas']; ?></div>
                    <div class="stat-label">Total de Aulas</div>
                </div>
            </div>
            
            <!-- Gráficos -->
            <div class="content-grid">
                <div class="chart-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        Distribuição de Frequência
                    </h3>
                    <div style="height: 300px; position: relative;">
                        <canvas id="frequenciaChart"></canvas>
                    </div>
                </div>
                
                <?php if (!empty($estatisticas_disciplinas)): ?>
                <div class="chart-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        Frequência por Disciplina
                    </h3>
                    <div style="height: 300px; position: relative;">
                        <canvas id="disciplinasChart"></canvas>
                    </div>
                </div>
                <?php else: ?>
                <div class="chart-section">
                    <h3 class="section-title">
                        <div class="section-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        Informações
                    </h3>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Dados insuficientes</h3>
                        <p>Não há dados suficientes para gerar gráficos por disciplina.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Tabela de faltas -->
            <div class="table-section">
                <h3 class="section-title">
                    <div class="section-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <?php 
                    if ($user_type === 'aluno') {
                        echo 'Histórico de Faltas';
                    } else {
                        echo 'Registro de Faltas dos Alunos';
                    }
                    ?>
                </h3>
                
                <div class="filters">
                    <select class="form-input filter-select" id="disciplinaFilter">
                        <option value="">Todas as disciplinas</option>
                        <?php 
                        $disciplinas = array_unique(array_column($faltas_data, 'disciplina_nome'));
                        foreach ($disciplinas as $disciplina): 
                        ?>
                            <option value="<?php echo htmlspecialchars($disciplina); ?>">
                                <?php echo htmlspecialchars($disciplina); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="date" class="form-input filter-select" id="dataFilter" placeholder="Filtrar por data">
                </div>
                
                <?php if (!empty($apenas_faltas)): ?>
                    <table class="data-table" id="faltasTable">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Disciplina</th>
                                <?php if ($user_type !== 'aluno'): ?>
                                    <th>Aluno</th>
                                <?php endif; ?>
                                <th>Professor</th>
                                <th>Conteúdo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apenas_faltas as $falta): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($falta['data'])); ?></td>
                                    <td><?php echo htmlspecialchars($falta['disciplina_nome']); ?></td>
                                    <?php if ($user_type !== 'aluno'): ?>
                                        <td><?php echo htmlspecialchars($falta['aluno_nome'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($falta['professor_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($falta['conteudo'] ?? 'Não informado'); ?></td>
                                    <td>
                                        <span class="status-badge falta">
                                            <i class="fas fa-times"></i>
                                            Falta
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-smile"></i>
                        <h3>Nenhuma falta registrada!</h3>
                        <p>
                            <?php if ($user_type === 'aluno'): ?>
                                Continue assim, sua frequência está excelente.
                            <?php else: ?>
                                Não há faltas registradas no período selecionado.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        <?php if ($user_type === 'aluno'): ?>
        // Gráfico de frequência (pizza)
        const frequenciaCtx = document.getElementById('frequenciaChart').getContext('2d');
        const frequenciaChart = new Chart(frequenciaCtx, {
            type: 'doughnut',
            data: {
                labels: ['Presenças', 'Faltas'],
                datasets: [{
                    data: [<?php echo $estatisticas['total_presencas']; ?>, <?php echo $estatisticas['total_faltas']; ?>],
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
                            usePointStyle: true,
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });
        
        <?php if (!empty($estatisticas_disciplinas)): ?>
        // Gráfico de disciplinas (barras)
        const disciplinasCtx = document.getElementById('disciplinasChart').getContext('2d');
        const disciplinasChart = new Chart(disciplinasCtx, {
            type: 'bar',
            data: {
                labels: [<?php echo '"' . implode('", "', array_column($estatisticas_disciplinas, 'disciplina')) . '"'; ?>],
                datasets: [{
                    label: 'Frequência (%)',
                    data: [<?php echo implode(', ', array_column($estatisticas_disciplinas, 'frequencia')); ?>],
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
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
        <?php endif; ?>
        
        // Filtros da tabela
        document.getElementById('disciplinaFilter').addEventListener('change', filterTable);
        document.getElementById('dataFilter').addEventListener('change', filterTable);
        
        function filterTable() {
            const disciplinaFilter = document.getElementById('disciplinaFilter').value.toLowerCase();
            const dataFilter = document.getElementById('dataFilter').value;
            
            const table = document.getElementById('faltasTable');
            if (!table) return;
            
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let row of rows) {
                let show = true;
                const cells = row.getElementsByTagName('td');
                
                // Filtro por disciplina
                if (disciplinaFilter && !cells[1].textContent.toLowerCase().includes(disciplinaFilter)) {
                    show = false;
                }
                
                // Filtro por data
                if (dataFilter) {
                    const rowDate = cells[0].textContent;
                    const [day, month, year] = rowDate.split('/');
                    const rowDateFormatted = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                    
                    if (rowDateFormatted !== dataFilter) show = false;
                }
                
                row.style.display = show ? '' : 'none';
            }
        }
        
        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.stat-card, .chart-section, .table-section');
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
