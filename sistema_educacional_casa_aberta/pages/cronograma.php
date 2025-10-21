<?php
/**
 * Página de Cronograma - Sistema Educacional
 * Visualização e edição do cronograma semanal
 * 
 * CORREÇÃO DE LÓGICA: Esta página agora usa a tabela `cronograma_semanal`
 * para o modelo fixo de aulas, separando-o do registro de aulas históricas (`aula`).
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

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_aula':
                if ($user_type !== 'coordenador') {
                    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                    exit();
                }
                
                $id = $_POST['id'];
                $field = $_POST['field'];
                $value = $_POST['value'];
                
                // Campos permitidos para atualização na tabela cronograma_semanal
                $allowed_fields = ['horario', 'disciplina_id', 'professor_id', 'conteudo'];
                if (!in_array($field, $allowed_fields)) {
                    echo json_encode(['success' => false, 'message' => 'Campo inválido']);
                    exit();
                }
                
                if ($field === 'disciplina_id') {
                    // Ao trocar disciplina, buscar o professor padrão
                    $stmt = $pdo->prepare("SELECT professor_id FROM disciplina WHERE id = ?");
                    $stmt->execute([$value]);
                    $disciplina = $stmt->fetch();
                    
                    if ($disciplina && $disciplina['professor_id']) {
                        // Atualiza disciplina_id e professor_id na tabela cronograma_semanal
                        $stmt = $pdo->prepare("UPDATE cronograma_semanal SET disciplina_id = ?, professor_id = ? WHERE id = ?");
                        $stmt->execute([$value, $disciplina['professor_id'], $id]);
                    } else {
                        // Atualiza apenas disciplina_id
                        $stmt = $pdo->prepare("UPDATE cronograma_semanal SET disciplina_id = ? WHERE id = ?");
                        $stmt->execute([$value, $id]);
                    }
                } else {
                    // Atualiza o campo na tabela cronograma_semanal
                    $stmt = $pdo->prepare("UPDATE cronograma_semanal SET $field = ? WHERE id = ?");
                    $stmt->execute([$value, $id]);
                }
                
                echo json_encode(['success' => true, 'message' => 'Aula atualizada com sucesso']);
                exit();
                
            case 'create_aula':
                if ($user_type !== 'coordenador') {
                    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                    exit();
                }
                
                $dia_semana = $_POST['dia_semana'];
                $horario = $_POST['horario'];
                $disciplina_id = $_POST['disciplina_id'];
                $conteudo = $_POST['conteudo'];
                
                // Buscar professor da disciplina
                $stmt = $pdo->prepare("SELECT professor_id FROM disciplina WHERE id = ?");
                $stmt->execute([$disciplina_id]);
                $disciplina = $stmt->fetch();
                $professor_id = $disciplina['professor_id'] ?? null;
                
                // INSERÇÃO NA NOVA TABELA: cronograma_semanal. A coluna 'data' foi removida.
                $stmt = $pdo->prepare("INSERT INTO cronograma_semanal (dia_semana, horario, disciplina_id, professor_id, conteudo, criado_por) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$dia_semana, $horario, $disciplina_id, $professor_id, $conteudo, $user_id]);
                
                echo json_encode(['success' => true, 'message' => 'Aula criada com sucesso']);
                exit();
                
            case 'delete_aula':
                if ($user_type !== 'coordenador') {
                    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                    exit();
                }
                
                $id = $_POST['id'];
                // DELEÇÃO NA NOVA TABELA: cronograma_semanal
                $stmt = $pdo->prepare("DELETE FROM cronograma_semanal WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Aula deletada com sucesso']);
                exit();
        }
    } catch (PDOException $e) {
        // Erro de chave duplicada (dia_semana, horario) pode ocorrer se tentar criar aula no mesmo slot.
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        exit();
    }
}

// Buscar aulas do cronograma (MODELO SEMANAL)
$dia_filter = $_GET['dia'] ?? '';

$where_clause = '';
$params = [];

if (!empty($dia_filter)) {
    $where_clause = 'WHERE cs.dia_semana = ?';
    $params[] = $dia_filter;
}

try {
    // CONSULTA NA NOVA TABELA: cronograma_semanal (cs)
    $stmt = $pdo->prepare("
        SELECT cs.*, d.nome as disciplina_nome, u.nome as professor_nome
        FROM cronograma_semanal cs
        LEFT JOIN disciplina d ON cs.disciplina_id = d.id
        LEFT JOIN Usuario u ON cs.professor_id = u.id
        $where_clause
        ORDER BY 
            FIELD(cs.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'),
            cs.horario ASC
    ");
    $stmt->execute($params);
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar disciplinas para dropdown
    $stmt = $pdo->prepare("SELECT id, nome FROM disciplina WHERE ativo = 1 ORDER BY nome ASC");
    $stmt->execute();
    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar professores para dropdown
    $stmt = $pdo->prepare("SELECT id, nome FROM Usuario WHERE tipo = 'professor' AND ativo = 1 ORDER BY nome ASC");
    $stmt->execute();
    $professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $aulas = [];
    $disciplinas = [];
    $professores = [];
    // Em produção, seria ideal logar o erro: error_log("Erro ao buscar cronograma: " . $e->getMessage());
}

$dias_semana = [
    'segunda' => 'Segunda-feira',
    'terca' => 'Terça-feira', 
    'quarta' => 'Quarta-feira',
    'quinta' => 'Quinta-feira',
    'sexta' => 'Sexta-feira'
    // Adicione 'sabado' e 'domingo' se necessário
];
?>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="cronograma-container">
        <!-- Cabeçalho da página -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-calendar-alt section-icon"></i>
                Cronograma de Aulas
            </h1>
            <p class="page-subtitle">Visualize e gerencie o cronograma semanal de aulas</p>
        </div>

        <!-- Controles e filtros -->
        <div class="controls-section">
            <div class="filters">
                <div class="filter-group">
                    <label for="diaFilter">Filtrar por dia:</label>
                    <select id="diaFilter" onchange="applyFilter()">
                        <option value="">Todos os dias</option>
                        <?php foreach ($dias_semana as $key => $nome): ?>
                        <option value="<?php echo $key; ?>" <?php echo $dia_filter === $key ? 'selected' : ''; ?>>
                            <?php echo $nome; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($user_type === 'coordenador'): ?>
                <div class="filter-group">
                    <button class="add-aula-btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i>
                        Adicionar Aula
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabela do cronograma -->
        <div class="cronograma-section">
            <div class="table-container">
                <table class="cronograma-table">
                    <thead>
                        <tr>
                            <th>Dia da Semana</th>
                            <th>Horário</th>
                            <th>Disciplina</th>
                            <th>Professor</th>
                            <th>Conteúdo</th>
                            <?php if ($user_type === 'coordenador'): ?>
                            <th>Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($aulas)): ?>
                        <tr>
                            <td colspan="<?php echo $user_type === 'coordenador' ? 6 : 5; ?>">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>Nenhuma aula encontrada</h3>
                                    <p>Não há aulas cadastradas no cronograma para o dia selecionado.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($aulas as $aula): ?>
                        <tr>
                            <td class="dia-cell">
                                <?php echo $dias_semana[$aula['dia_semana']] ?? $aula['dia_semana']; ?>
                            </td>
                            <td class="horario-cell">
                                <?php if ($user_type === 'coordenador'): ?>
                                <input type="time" 
                                       value="<?php echo $aula['horario']; ?>" 
                                       class="editable-field"
                                       onchange="updateField(<?php echo $aula['id']; ?>, 'horario', this.value)">
                                <?php else: ?>
                                <?php echo date('H:i', strtotime($aula['horario'])); ?>
                                <?php endif; ?>
                            </td>
                            <td class="disciplina-cell">
                                <?php if ($user_type === 'coordenador'): ?>
                                <select class="editable-field" 
                                        onchange="updateField(<?php echo $aula['id']; ?>, 'disciplina_id', this.value)">
                                    <?php foreach ($disciplinas as $disciplina): ?>
                                    <option value="<?php echo $disciplina['id']; ?>" 
                                            <?php echo $aula['disciplina_id'] == $disciplina['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($disciplina['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <?php echo htmlspecialchars($aula['disciplina_nome'] ?? 'Sem disciplina'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="professor-cell">
                                <?php if ($user_type === 'coordenador'): ?>
                                <select class="editable-field" 
                                        onchange="updateField(<?php echo $aula['id']; ?>, 'professor_id', this.value)">
                                    <option value="">Sem professor</option>
                                    <?php foreach ($professores as $professor): ?>
                                    <option value="<?php echo $professor['id']; ?>" 
                                            <?php echo $aula['professor_id'] == $professor['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($professor['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <?php echo htmlspecialchars($aula['professor_nome'] ?? 'Não atribuído'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="conteudo-cell">
                                <?php if ($user_type === 'coordenador'): ?>
                                <input type="text" 
                                       value="<?php echo htmlspecialchars($aula['conteudo'] ?? ''); ?>" 
                                       class="editable-field"
                                       onchange="updateField(<?php echo $aula['id']; ?>, 'conteudo', this.value)">
                                <?php else: ?>
                                <?php echo htmlspecialchars($aula['conteudo'] ?? 'Conteúdo padrão'); ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($user_type === 'coordenador'): ?>
                            <td class="actions-cell">
                                <button class="btn-action btn-delete" onclick="deleteAula(<?php echo $aula['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para Adicionar Aula -->
    <div id="createAulaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adicionar Nova Aula ao Cronograma</h2>
                <button class="modal-close" onclick="closeCreateModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="createAulaForm">
                <div class="form-group">
                    <label for="modalDiaSemana">Dia da Semana:</label>
                    <select id="modalDiaSemana" name="dia_semana" required>
                        <?php foreach ($dias_semana as $key => $nome): ?>
                        <option value="<?php echo $key; ?>"><?php echo $nome; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="modalHorario">Horário:</label>
                    <input type="time" id="modalHorario" name="horario" required>
                </div>
                <div class="form-group">
                    <label for="modalDisciplina">Disciplina:</label>
                    <select id="modalDisciplina" name="disciplina_id" required>
                        <option value="">Selecione a Disciplina</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                        <option value="<?php echo $disciplina['id']; ?>"><?php echo htmlspecialchars($disciplina['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- O professor_id será buscado automaticamente no backend -->
                <div class="form-group">
                    <label for="modalConteudo">Conteúdo Padrão:</label>
                    <input type="text" id="modalConteudo" name="conteudo" placeholder="Ex: Introdução à Álgebra" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Aula</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mensagem de feedback -->
    <div id="feedbackMessage" class="message"></div>

</div>

<script>
    // Função para aplicar o filtro por dia
    function applyFilter() {
        const dia = document.getElementById('diaFilter').value;
        window.location.href = 'cronograma.php' + (dia ? '?dia=' + dia : '');
    }

    // Função para abrir o modal de criação
    function openCreateModal() {
        document.getElementById('createAulaModal').style.display = 'flex';
    }

    // Função para fechar o modal de criação
    function closeCreateModal() {
        document.getElementById('createAulaModal').style.display = 'none';
        document.getElementById('createAulaForm').reset();
    }

    // Função para exibir mensagem de feedback
    function showFeedback(message, type = 'success') {
        const msgElement = document.getElementById('feedbackMessage');
        msgElement.textContent = message;
        msgElement.className = 'message show message-' + type;
        setTimeout(() => {
            msgElement.className = 'message';
        }, 3000);
    }

    // Função AJAX genérica para atualização de campo
    function updateField(id, field, value) {
        if (confirm(`Tem certeza que deseja alterar o campo ${field} da aula ${id}?`)) {
            fetch('cronograma.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax: true,
                    action: 'update_aula',
                    id: id,
                    field: field,
                    value: value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFeedback(data.message);
                    // Recarregar a página ou atualizar a linha se o campo for 'disciplina_id'
                    if (field === 'disciplina_id') {
                        // Recarregar para atualizar o nome do professor
                        window.location.reload(); 
                    }
                } else {
                    showFeedback(data.message, 'error');
                }
            })
            .catch(error => {
                showFeedback('Erro de comunicação com o servidor.', 'error');
                console.error('Erro:', error);
            });
        }
    }

    // Função AJAX para deleção de aula
    function deleteAula(id) {
        if (confirm('Tem certeza que deseja deletar esta aula do cronograma semanal?')) {
            fetch('cronograma.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    ajax: true,
                    action: 'delete_aula',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFeedback(data.message);
                    // Remover a linha da tabela
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showFeedback(data.message, 'error');
                }
            })
            .catch(error => {
                showFeedback('Erro de comunicação com o servidor.', 'error');
                console.error('Erro:', error);
            });
        }
    }

    // Submissão do formulário de criação de aula
    document.getElementById('createAulaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const params = new URLSearchParams(formData);
        params.append('ajax', true);
        params.append('action', 'create_aula');

        fetch('cronograma.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFeedback(data.message);
                closeCreateModal();
                // Recarregar a página para mostrar a nova aula
                setTimeout(() => window.location.reload(), 1000); 
            } else {
                showFeedback(data.message, 'error');
            }
        })
        .catch(error => {
            showFeedback('Erro de comunicação com o servidor.', 'error');
            console.error('Erro:', error);
        });
    });

    // Filtro de URL
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const dia = urlParams.get('dia');
        if (dia) {
            document.getElementById('diaFilter').value = dia;
        }
    });
</script>

<style>
    /* Estilos CSS (mantidos do original para evitar quebra de layout) */
    :root {
        --primary-color: #e53935; /* Vermelho */
        --secondary-color: #ffb300; /* Amarelo */
        --text-primary: #333;
        --text-secondary: #666;
        --bg-color: #f4f7f9;
        --card-bg: #ffffff;
        --border-color: #e0e0e0;
        --gray-50: #f9f9f9;
        --gray-200: #eeeeee;
        --gray-300: #e0e0e0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--bg-color);
        color: var(--text-primary);
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    .main-content {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .cronograma-container {
        padding: 20px;
    }

    .page-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-icon {
        font-size: 2rem;
        background: var(--primary-color);
        color: white;
        padding: 0.5rem;
        border-radius: 8px;
        line-height: 1;
    }

    .page-subtitle {
        color: var(--text-secondary);
        font-size: 1.125rem;
        margin-top: 0.5rem;
    }

    .controls-section {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
        margin-bottom: 2rem;
    }

    .filters {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-group label {
        font-weight: 600;
        color: var(--text-primary);
    }

    .filter-group select {
        padding: 0.5rem;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background: var(--bg-color);
        color: var(--text-primary);
    }

    .add-aula-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: background-color 0.2s ease;
    }

    .add-aula-btn:hover {
        background: #c62828;
    }

    .table-container {
        background: var(--card-bg);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
    }

    .cronograma-table {
        width: 100%;
        border-collapse: collapse;
    }

    .cronograma-table th {
        background: var(--gray-50);
        color: var(--text-secondary);
        font-weight: 600;
        padding: 1rem;
        text-align: left;
        border-bottom: 2px solid var(--border-color);
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .cronograma-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }

    .cronograma-table tr:hover {
        background: var(--gray-50);
    }

    .dia-cell {
        font-weight: 600;
        color: var(--text-primary);
    }

    .horario-cell {
        font-weight: 600;
        color: var(--primary-color);
    }

    .editable-field {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        background: var(--bg-color);
        color: var(--text-primary);
    }

    .btn-action {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-delete {
        background: #ffebee;
        color: #c62828;
    }

    .btn-delete:hover {
        background: #ffcdd2;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: var(--card-bg);
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        margin: 0;
        color: var(--text-primary);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-secondary);
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-actions {
        padding: 1.5rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    .form-group {
        margin-bottom: 1.5rem;
        padding: 0 1.5rem;
    }

    .form-group:first-of-type {
        padding-top: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--bg-color);
        color: var(--text-primary);
    }

    .btn {
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary:hover {
        background: #c62828;
    }

    .btn-secondary {
        background: var(--gray-200);
        color: var(--text-primary);
    }

    .btn-secondary:hover {
        background: var(--gray-300);
    }

    .message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 1001;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .message.show {
        transform: translateX(0);
    }

    .message-success {
        background: #4caf50;
    }

    .message-error {
        background: #f44336;
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 10px;
        }
        
        .filters {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-group {
            justify-content: space-between;
        }
        
        .cronograma-table {
            font-size: 0.875rem;
        }
        
        .cronograma-table th,
        .cronograma-table td {
            padding: 0.75rem 0.5rem;
        }

        .page-title {
            font-size: 1.5rem;
        }
    }
</style>

</body>
</html>