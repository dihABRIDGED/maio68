<?php
/**
 * Página de Gerenciamento de Usuários - Moderna
 * Sistema Educacional - TCC
 */

session_start();

// Verificar se o usuário está logado e é coordenador
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_type'] !== 'coordenador') {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/connection.php';

$success_message = "";
$error_message = "";

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'create':
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $tipo = $_POST['tipo'];
                $login = trim($_POST['login']);
                $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                
                // Verificar se login e email são únicos
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE login = ? OR email = ?");
                $stmt->execute([$login, $email]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Login ou email já existem!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("INSERT INTO Usuario (nome, email, tipo, login, senha, ativo) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$nome, $email, $tipo, $login, $senha]);
                
                echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso!']);
                exit();
                
            case 'update':
                $id = $_POST['id'];
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $login = trim($_POST['login']);
                $ativo = isset($_POST['ativo']) ? 1 : 0;
                
                // Verificar se login e email são únicos (exceto para o próprio usuário)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Usuario WHERE (login = ? OR email = ?) AND id != ?");
                $stmt->execute([$login, $email, $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Login ou email já existem!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("UPDATE Usuario SET nome = ?, email = ?, login = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$nome, $email, $login, $ativo, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
                exit();
                
            case 'delete':
                $id = $_POST['id'];
                
                // Verificar se não é o próprio usuário
                if ($id == $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'message' => 'Você não pode deletar sua própria conta!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("DELETE FROM Usuario WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Usuário deletado com sucesso!']);
                exit();
                
            case 'get_user':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("SELECT * FROM Usuario WHERE id = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'user' => $user]);
                exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        exit();
    }
}

// Buscar usuários com filtros
$search = $_GET['search'] ?? '';
$tipo_filter = $_GET['tipo'] ?? '';
$ativo_filter = $_GET['ativo'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(nome LIKE ? OR email LIKE ? OR login LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($tipo_filter)) {
    $where_conditions[] = "tipo = ?";
    $params[] = $tipo_filter;
}

if ($ativo_filter !== '') {
    $where_conditions[] = "ativo = ?";
    $params[] = $ativo_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $pdo->prepare("SELECT * FROM Usuario $where_clause ORDER BY nome ASC");
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM Usuario")->fetchColumn(),
    'alunos' => $pdo->query("SELECT COUNT(*) FROM Usuario WHERE tipo = 'aluno'")->fetchColumn(),
    'professores' => $pdo->query("SELECT COUNT(*) FROM Usuario WHERE tipo = 'professor'")->fetchColumn(),
    'coordenadores' => $pdo->query("SELECT COUNT(*) FROM Usuario WHERE tipo = 'coordenador'")->fetchColumn(),
    'ativos' => $pdo->query("SELECT COUNT(*) FROM Usuario WHERE ativo = 1")->fetchColumn()
];

$username = $_SESSION['username'] ?? 'Coordenador';
?>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>
                <p>Administre usuários do sistema educacional</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Novo Usuário
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total de Usuários</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['alunos']; ?></div>
                    <div class="stat-label">Alunos</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['professores']; ?></div>
                    <div class="stat-label">Professores</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['coordenadores']; ?></div>
                    <div class="stat-label">Coordenadores</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['ativos']; ?></div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
            </div>
        </div>

        <div class="filters-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por nome, email ou login..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <select id="tipoFilter">
                    <option value="">Todos os tipos</option>
                    <option value="aluno" <?php echo $tipo_filter === 'aluno' ? 'selected' : ''; ?>>Alunos</option>
                    <option value="professor" <?php echo $tipo_filter === 'professor' ? 'selected' : ''; ?>>Professores</option>
                    <option value="coordenador" <?php echo $tipo_filter === 'coordenador' ? 'selected' : ''; ?>>Coordenadores</option>
                </select>
                <select id="ativoFilter">
                    <option value="">Todos os status</option>
                    <option value="1" <?php echo $ativo_filter === '1' ? 'selected' : ''; ?>>Ativos</option>
                    <option value="0" <?php echo $ativo_filter === '0' ? 'selected' : ''; ?>>Inativos</option>
                </select>
                <button class="btn btn-secondary" onclick="applyFilters()">
                    <i class="fas fa-filter"></i> Aplicar
                </button>
            </div>
        </div>

        <div class="table-section card">
            <h2>Lista de Usuários (<?php echo count($usuarios); ?>)</h2>
            
            <?php if (empty($usuarios)): ?>
                <div class="empty-state">
                    <i class="fas fa-frown"></i>
                    <p>Nenhum usuário encontrado com os filtros aplicados.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Login</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th class="actions-col">Ações</th> </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $user): ?>
                                <tr data-id="<?php echo $user['id']; ?>">
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['login']); ?></td>
                                    <td><span class="badge type-<?php echo $user['tipo']; ?>"><?php echo ucfirst($user['tipo']); ?></span></td>
                                    <td>
                                        <span class="badge status-<?php echo $user['ativo'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td class="actions-col">
                                        <button class="btn btn-sm btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nome']); ?>')">
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

<div id="createModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Novo Usuário</h3>
            <button class="close-btn" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form id="createForm">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label for="create_nome">Nome Completo:</label>
                    <input type="text" name="nome" id="create_nome" required>
                </div>
                <div class="form-group">
                    <label for="create_email">Email:</label>
                    <input type="email" name="email" id="create_email" required>
                </div>
                <div class="form-group">
                    <label for="create_tipo">Tipo de Usuário:</label>
                    <select name="tipo" id="create_tipo" required>
                        <option value="aluno">Aluno</option>
                        <option value="professor">Professor</option>
                        <option value="coordenador">Coordenador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="create_login">Login:</label>
                    <input type="text" name="login" id="create_login" required>
                </div>
                <div class="form-group">
                    <label for="create_senha">Senha:</label>
                    <input type="password" name="senha" id="create_senha" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Criar Usuário</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Usuário</h3>
            <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form id="editForm">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_nome">Nome Completo:</label>
                    <input type="text" name="nome" id="edit_nome" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label for="edit_login">Login:</label>
                    <input type="text" name="login" id="edit_login" required>
                </div>
                <div class="form-group">
                    <label class="checkbox-label" for="edit_ativo">
                        <input type="checkbox" name="ativo" id="edit_ativo" value="1">
                        Usuário Ativo
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

<div id="messageContainer">
    <div id="successMessage" class="message success" style="display: none;"></div>
    <div id="errorMessage" class="message error" style="display: none;"></div>
</div>

<form method="POST" action="usuarios.php" id="deleteForm" style="display: none;">
    <input type="hidden" name="ajax" value="1">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<style>
    /* Estilos TCC 2.0 - A maioria deveria estar em modern.css */
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

    /* Stats Grid - Adaptado para 5 cards */
    .stats-grid {
        display: grid;
        /* Garante que 5 cards caibam na horizontal em telas grandes */
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

    /* Corrigido para evitar rolagem horizontal desnecessária: */
    /* Removemos 'table-responsive' e aplicamos 'table-layout: fixed' */
    .table-container {
        width: 100%;
        overflow-x: auto; /* Mantém a rolagem apenas se estritamente necessário em telas muito pequenas */
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        table-layout: fixed; /* Fixa o layout para respeitar as larguras das colunas */
    }

    table th, table td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        word-wrap: break-word; /* Permite a quebra de linha em palavras longas */
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

    /* Define a largura para a coluna de Ações e assegura visibilidade dos botões */
    .actions-col {
        width: 100px; /* Largura suficiente para os dois botões */
        text-align: center;
        white-space: nowrap; /* Impede a quebra de linha dentro da célula de botões */
    }
    
    /* Larguras relativas para as outras colunas, permitindo ajuste automático */
    table th:nth-child(1), table td:nth-child(1) { width: 50px; } /* ID */
    table th:nth-child(2), table td:nth-child(2) { width: 20%; } /* Nome */
    table th:nth-child(3), table td:nth-child(3) { width: 30%; } /* Email */
    table th:nth-child(4), table td:nth-child(4) { width: 20%; } /* Login */
    table th:nth-child(5), table td:nth-child(5) { width: 10%; } /* Tipo */
    table th:nth-child(6), table td:nth-child(6) { width: 10%; } /* Status */
    /* A última coluna (ações-col) já tem 100px */


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
    
    .type-aluno { background-color: rgba(52, 152, 219, 0.1); color: #3498db; }
    .type-professor { background-color: rgba(230, 126, 34, 0.1); color: #e67e22; }
    .type-coordenador { background-color: rgba(155, 89, 182, 0.1); color: #9b59b6; }

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
        /* Z-INDEX CORRIGIDO: Aumentado para garantir a sobreposição */
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
        /* Z-INDEX CORRIGIDO: Aumentado para garantir a sobreposição */
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
        const tipo = document.getElementById('tipoFilter').value;
        const ativo = document.getElementById('ativoFilter').value;
        
        let url = 'usuarios.php?';
        if (search) url += `search=${encodeURIComponent(search)}&`;
        if (tipo) url += `tipo=${encodeURIComponent(tipo)}&`;
        if (ativo) url += `ativo=${encodeURIComponent(ativo)}&`;
        
        window.location.href = url.slice(0, -1); // Remove o último '&'
    }

    // Editar Usuário (Função Assíncrona para buscar dados)
    function editUser(id) {
        fetch('usuarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax=1&action=get_user&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('edit_id').value = user.id;
                document.getElementById('edit_nome').value = user.nome;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_login').value = user.login;
                document.getElementById('edit_ativo').checked = user.ativo == 1;
                document.getElementById('editModal').classList.add('show');
            } else {
                showError('Erro ao buscar dados do usuário: ' + data.message);
            }
        })
        .catch(() => showError('Erro de conexão ao buscar dados.'));
    }

    // Deletar Usuário
    function deleteUser(id, nome) {
        if (confirm(`Tem certeza que deseja remover o usuário ${nome}? Esta ação é irreversível.`)) {
            fetch('usuarios.php', {
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
            .catch(() => showError('Erro de conexão ao deletar usuário.'));
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
        fetch('usuarios.php', {
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