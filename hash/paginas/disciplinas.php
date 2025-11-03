<?php
/**
 * Página de Gerenciamento de Disciplinas - Moderna
 * Sistema Educacional - TCC
 */

session_start();

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'coordenador') {
    header('Location: ../index.php');
    exit();
}

require_once '../inclusos/conexao.php';

$success_message = "";
$error_message = "";

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create':
                $nome = trim($_POST['nome']);
                $professor_id = $_POST['professor_id'];
                
                // Verificar se disciplina já existe
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM disciplina WHERE nome = ?");
                $stmt->execute([$nome]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Já existe uma disciplina com este nome!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("INSERT INTO disciplina (nome, professor_id, ativo) VALUES (?, ?, 1)");
                $stmt->execute([$nome, $professor_id]);
                
                echo json_encode(['success' => true, 'message' => 'Disciplina criada com sucesso!']);
                exit();
                
            case 'update':
                $id = $_POST['id'];
                $nome = trim($_POST['nome']);
                $professor_id = $_POST['professor_id'];
                $ativo = isset($_POST['ativo']) ? 1 : 0;
                
                // Verificar se nome já existe (exceto para a própria disciplina)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM disciplina WHERE nome = ? AND id != ?");
                $stmt->execute([$nome, $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Já existe uma disciplina com este nome!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("UPDATE disciplina SET nome = ?, professor_id = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$nome, $professor_id, $ativo, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Disciplina atualizada com sucesso!']);
                exit();
                
            case 'delete':
                $id = $_POST['id'];
                
                // Verificar se existem matrículas nesta disciplina
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM matricula WHERE disciplina_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Não é possível excluir disciplina com alunos matriculados!']);
                    exit();
                }
                
                // Verificar se existem aulas nesta disciplina
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM aula WHERE disciplina_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Não é possível excluir disciplina com aulas cadastradas!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("DELETE FROM disciplina WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Disciplina excluída com sucesso!']);
                exit();
                
            case 'get_disciplina':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("
                    SELECT d.*, u.nome as professor_nome 
                    FROM disciplina d 
                    LEFT JOIN usuario u ON d.professor_id = u.id 
                    WHERE d.id = ?
                ");
                $stmt->execute([$id]);
                $disciplina = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'disciplina' => $disciplina]);
                exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        exit();
    }
}

// Buscar professores para os selects
$stmt = $pdo->prepare("SELECT id, nome FROM usuario WHERE tipo = 'professor' AND ativo = 1 ORDER BY nome");
$stmt->execute();
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar disciplinas com filtros
$search = $_GET['search'] ?? '';
$professor_filter = $_GET['professor'] ?? '';
$ativo_filter = $_GET['ativo'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "d.nome LIKE ?";
    $params[] = "%$search%";
}

if (!empty($professor_filter)) {
    $where_conditions[] = "d.professor_id = ?";
    $params[] = $professor_filter;
}

if ($ativo_filter !== '') {
    $where_conditions[] = "d.ativo = ?";
    $params[] = $ativo_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("
    SELECT d.*, u.nome as professor_nome 
    FROM disciplina d 
    LEFT JOIN usuario u ON d.professor_id = u.id 
    $where_clause 
    ORDER BY d.nome ASC
");
$stmt->execute($params);
$disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM disciplina")->fetchColumn(),
    'ativas' => $pdo->query("SELECT COUNT(*) FROM disciplina WHERE ativo = 1")->fetchColumn(),
    'inativas' => $pdo->query("SELECT COUNT(*) FROM disciplina WHERE ativo = 0")->fetchColumn(),
    'com_professor' => $pdo->query("SELECT COUNT(*) FROM disciplina WHERE professor_id IS NOT NULL")->fetchColumn(),
    'sem_professor' => $pdo->query("SELECT COUNT(*) FROM disciplina WHERE professor_id IS NULL")->fetchColumn()
];

$username = $_SESSION['username'] ?? 'Coordenador';
?>

<?php include '../inclusos/cabecalho.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-book"></i> Gerenciar Disciplinas</h1>
                <p>Administre as disciplinas do sistema educacional</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Nova Disciplina
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total de Disciplinas</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['ativas']; ?></div>
                    <div class="stat-label">Disciplinas Ativas</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['inativas']; ?></div>
                    <div class="stat-label">Disciplinas Inativas</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['com_professor']; ?></div>
                    <div class="stat-label">Com Professor</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['sem_professor']; ?></div>
                    <div class="stat-label">Sem Professor</div>
                </div>
            </div>
        </div>

        <div class="filters-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por nome da disciplina..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <select id="professorFilter">
                    <option value="">Todos os professores</option>
                    <?php foreach ($professores as $professor): ?>
                        <option value="<?php echo $professor['id']; ?>" <?php echo $professor_filter == $professor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($professor['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="ativoFilter">
                    <option value="">Todos os status</option>
                    <option value="1" <?php echo $ativo_filter === '1' ? 'selected' : ''; ?>>Ativas</option>
                    <option value="0" <?php echo $ativo_filter === '0' ? 'selected' : ''; ?>>Inativas</option>
                </select>
                <button class="btn btn-secondary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Aplicar
                </button>
            </div>
        </div>

        <div class="table-section card">
            <h2>Lista de Disciplinas (<?php echo count($disciplinas); ?>)</h2>
            
            <?php if (empty($disciplinas)): ?>
                <div class="empty-state">
                    <i class="fas fa-frown"></i>
                    <p>Nenhuma disciplina encontrada com os filtros aplicados.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Professor</th>
                                <th>Status</th>
                                <th class="actions-col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disciplinas as $disciplina): ?>
                                <tr data-id="<?php echo $disciplina['id']; ?>">
                                    <td><?php echo htmlspecialchars($disciplina['id']); ?></td>
                                    <td><?php echo htmlspecialchars($disciplina['nome']); ?></td>
                                    <td>
                                        <?php if (!empty($disciplina['professor_nome'])): ?>
                                            <?php echo htmlspecialchars($disciplina['professor_nome']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Não atribuído</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo $disciplina['ativo'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $disciplina['ativo'] ? 'Ativa' : 'Inativa'; ?>
                                        </span>
                                    </td>
                                    <td class="actions-col">
                                        <button class="btn btn-sm btn-edit" onclick="editDisciplina(<?php echo $disciplina['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-delete" onclick="deleteDisciplina(<?php echo $disciplina['id']; ?>, '<?php echo htmlspecialchars($disciplina['nome']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Criar Disciplina -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nova Disciplina</h3>
            <button class="close-btn" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form id="createForm">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label for="create_nome">Nome da Disciplina:</label>
                    <input type="text" name="nome" id="create_nome" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="create_professor_id">Professor Responsável:</label>
                    <select name="professor_id" id="create_professor_id" required>
                        <option value="">Selecione um professor</option>
                        <?php foreach ($professores as $professor): ?>
                            <option value="<?php echo $professor['id']; ?>">
                                <?php echo htmlspecialchars($professor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Criar Disciplina</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Disciplina -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Disciplina</h3>
            <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form id="editForm">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_nome">Nome da Disciplina:</label>
                    <input type="text" name="nome" id="edit_nome" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="edit_professor_id">Professor Responsável:</label>
                    <select name="professor_id" id="edit_professor_id" required>
                        <option value="">Selecione um professor</option>
                        <?php foreach ($professores as $professor): ?>
                            <option value="<?php echo $professor['id']; ?>">
                                <?php echo htmlspecialchars($professor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label" for="edit_ativo">
                        <input type="checkbox" name="ativo" id="edit_ativo" value="1">
                        Disciplina Ativa
                    </label>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Container para mensagens -->
<div id="messageContainer">
    <div id="successMessage" class="message success" style="display: none;"></div>
    <div id="errorMessage" class="message error" style="display: none;"></div>
</div>

<style>
    /* Estilos TCC 2.0 - Compatível com modern.css */
    :root {
        --primary-red: #e74c3c;
        --secondary-blue: #3498db;
        --text-primary: #34495e;
        --text-secondary: #7f8c8d;
        --background: #ecf0f1;
        --card-bg: #ffffff;
        --border-color: #bdc3c7;
        --input-bg: #ecf0f1;
        --success-color: #27ae60;
        --danger-color: #c0392b;
        --warning-color: #f39c12;
    }

    /* Layout Principal */
    .main-content {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .container {
        padding: 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--border-color);
    }

    .page-header h1 {
        font-size: 2rem;
        color: var(--text-primary);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: var(--card-bg);
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-left: 5px solid var(--primary-red);
    }

    .stat-icon {
        font-size: 2.5rem;
        color: var(--secondary-blue);
        margin-right: 15px;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-red);
    }

    .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    /* Filtros */
    .filters-section {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 15px;
        background: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .search-box {
        flex-grow: 1;
        display: flex;
        align-items: center;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 0 10px;
    }

    .search-box i {
        color: var(--text-secondary);
        margin-right: 10px;
    }

    .search-box input {
        border: none;
        padding: 10px 0;
        width: 100%;
        background: transparent;
        color: var(--text-primary);
        outline: none;
    }

    .filter-group {
        display: flex;
        gap: 10px;
    }

    .filter-group select {
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--input-bg);
        color: var(--text-primary);
    }

    /* Tabela */
    .table-section {
        background: var(--card-bg);
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .table-container {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        table-layout: fixed;
    }

    table th, table td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        word-wrap: break-word;
    }

    table th {
        background-color: var(--input-bg);
        font-weight: 700;
        color: var(--text-primary);
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    table tbody tr:hover {
        background-color: #f7f7f7;
    }

    .actions-col {
        width: 100px;
        text-align: center;
        white-space: nowrap;
    }
    
    /* Larguras das colunas */
    table th:nth-child(1), table td:nth-child(1) { width: 50px; } /* ID */
    table th:nth-child(2), table td:nth-child(2) { width: 30%; } /* Nome */
    table th:nth-child(3), table td:nth-child(3) { width: 40%; } /* Professor */
    table th:nth-child(4), table td:nth-child(4) { width: 15%; } /* Status */
    table th:nth-child(5), table td:nth-child(5) { width: 100px; } /* Ações */

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .status-active {
        background-color: rgba(39, 174, 96, 0.1);
        color: var(--success-color);
    }

    .status-inactive {
        background-color: rgba(192, 57, 43, 0.1);
        color: var(--danger-color);
    }

    .text-muted {
        color: var(--text-secondary);
        font-style: italic;
    }

    /* Botões */
    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s;
    }

    .btn-primary {
        background-color: var(--primary-red);
        color: white;
    }

    .btn-primary:hover {
        background-color: #c0392b;
    }

    .btn-secondary {
        background-color: var(--text-secondary);
        color: white;
    }
    
    .btn-secondary:hover {
        background-color: #6c7a89;
    }

    .btn-edit {
        background-color: var(--secondary-blue);
        color: white;
        margin-right: 5px;
    }

    .btn-edit:hover {
        background-color: #2980b9;
    }

    .btn-delete {
        background-color: var(--danger-color);
        color: white;
    }

    .btn-delete:hover {
        background-color: #a93226;
    }

    .btn-sm {
        padding: 6px 10px;
        font-size: 0.8rem;
    }

    /* MODALS */
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.6);
        justify-content: center;
        align-items: center;
    }
    
    .modal.show {
        display: flex;
    }

    .modal-content {
        background-color: var(--card-bg);
        margin: auto;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        animation: fadeIn 0.3s;
        transform: translateY(0);
        transition: transform 0.3s ease-out;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.5rem;
        color: var(--primary-red);
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--text-primary);
        transition: color 0.2s;
    }

    .close-btn:hover {
        color: var(--primary-red);
    }

    .modal-body {
        padding: 1.5rem;
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
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-group input,
    .form-group select {
        display: block; 
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--input-bg);
        color: var(--text-primary);
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }

    .checkbox-label input[type="checkbox"] {
        width: auto;
    }

    /* Mensagens de Feedback */
    .message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 1100;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }

    .message.success {
        background-color: var(--success-color);
    }

    .message.error {
        background-color: var(--danger-color);
    }

    .message.show {
        transform: translateX(0);
    }

    /* Estado vazio */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--border-color);
    }

    @media (max-width: 768px) {
        .filters-section {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
            justify-content: space-between;
        }

        .filter-group select, .filter-group button {
            flex-grow: 1;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }
        
        .page-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
    }
</style>

<script>
    // Função para mostrar mensagem de sucesso
    function showSuccess(message) {
        const messageBox = document.getElementById('successMessage');
        messageBox.textContent = message;
        messageBox.classList.add('show');
        setTimeout(() => {
            messageBox.classList.remove('show');
        }, 3000);
    }

    // Função para mostrar mensagem de erro
    function showError(message) {
        const messageBox = document.getElementById('errorMessage');
        messageBox.textContent = message;
        messageBox.classList.add('show');
        setTimeout(() => {
            messageBox.classList.remove('show');
        }, 3000);
    }

    // Abrir e Fechar Modals
    function openCreateModal() {
        document.getElementById('createForm').reset();
        document.getElementById('createModal').classList.add('show');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
    }

    // Aplicar Filtros
    function applyFilters() {
        const search = document.getElementById('searchInput').value;
        const professor = document.getElementById('professorFilter').value;
        const ativo = document.getElementById('ativoFilter').value;
        
        let url = 'disciplinas.php?';
        if (search) url += `search=${encodeURIComponent(search)}&`;
        if (professor) url += `professor=${encodeURIComponent(professor)}&`;
        if (ativo) url += `ativo=${encodeURIComponent(ativo)}&`;
        
        window.location.href = url.slice(0, -1); // Remove o último '&'
    }

    // Editar Disciplina
    function editDisciplina(id) {
        fetch('disciplinas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax=1&action=get_disciplina&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const disciplina = data.disciplina;
                document.getElementById('edit_id').value = disciplina.id;
                document.getElementById('edit_nome').value = disciplina.nome;
                document.getElementById('edit_professor_id').value = disciplina.professor_id;
                document.getElementById('edit_ativo').checked = disciplina.ativo == 1;
                document.getElementById('editModal').classList.add('show');
            } else {
                showError('Erro ao buscar dados da disciplina: ' + data.message);
            }
        })
        .catch(() => showError('Erro de conexão ao buscar dados.'));
    }

    // Deletar Disciplina
    function deleteDisciplina(id, nome) {
        if (confirm(`Tem certeza que deseja excluir a disciplina "${nome}"? Esta ação é irreversível.`)) {
            fetch('disciplinas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=delete&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    document.querySelector(`tr[data-id="${id}"]`).remove();
                } else {
                    showError(data.message);
                }
            })
            .catch(() => showError('Erro de conexão ao excluir disciplina.'));
        }
    }

    // Submissão dos Formulários (AJAX)
    document.getElementById('createForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        submitForm(formData, 'createModal');
    });

    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        // Adiciona o valor '0' para 'ativo' se o checkbox não estiver marcado
        if (!document.getElementById('edit_ativo').checked) {
             formData.append('ativo', '0');
        }
        submitForm(formData, 'editModal');
    });

    function submitForm(formData, modalId) {
        fetch('disciplinas.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
                closeModal(modalId);
                // Recarrega a página para atualizar a tabela
                setTimeout(() => window.location.reload(), 500);
            } else {
                showError(data.message);
            }
        })
        .catch(() => showError('Erro de conexão ao salvar dados.'));
    }

    // Animações de entrada na tabela
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.stat-card, .table-section');
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