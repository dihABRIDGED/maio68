<?php
/**
 * Página de Gerenciamento de Disciplinas - Moderna
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
                $descricao = trim($_POST['descricao']);
                $professor_id = $_POST['professor_id'] ?: null;
                $carga_horaria = $_POST['carga_horaria'];
                $ativo = isset($_POST['ativo']) ? 1 : 0;
                
                // Verificar se o nome da disciplina já existe
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM disciplina WHERE nome = ?");
                $stmt->execute([$nome]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Já existe uma disciplina com este nome!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("INSERT INTO disciplina (nome, descricao, professor_id, carga_horaria, ativo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $descricao, $professor_id, $carga_horaria, $ativo]);
                
                echo json_encode(['success' => true, 'message' => 'Disciplina criada com sucesso!']);
                exit();
                
            case 'update':
                $id = $_POST['id'];
                $nome = trim($_POST['nome']);
                $descricao = trim($_POST['descricao']);
                $professor_id = $_POST['professor_id'] ?: null;
                $carga_horaria = $_POST['carga_horaria'];
                $ativo = isset($_POST['ativo']) ? 1 : 0;
                
                // Verificar se o nome da disciplina já existe (exceto para a própria disciplina)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM disciplina WHERE nome = ? AND id != ?");
                $stmt->execute([$nome, $id]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Já existe uma disciplina com este nome!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("UPDATE disciplina SET nome = ?, descricao = ?, professor_id = ?, carga_horaria = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$nome, $descricao, $professor_id, $carga_horaria, $ativo, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Disciplina atualizada com sucesso!']);
                exit();
                
            case 'delete':
                $id = $_POST['id'];
                
                // Verificar se a disciplina tem aulas associadas
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM aula WHERE turma_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Não é possível deletar disciplina com aulas associadas!']);
                    exit();
                }
                
                // Verificar se a disciplina tem matrículas associadas
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM matricula WHERE disciplina_id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Não é possível deletar disciplina com alunos matriculados!']);
                    exit();
                }
                
                $stmt = $pdo->prepare("DELETE FROM disciplina WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Disciplina deletada com sucesso!']);
                exit();
                
            case 'get_disciplina':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("SELECT * FROM disciplina WHERE id = ?");
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

// Buscar professores para o dropdown
$stmt = $pdo->prepare("SELECT id, nome FROM Usuario WHERE tipo = 'professor' AND ativo = 1 ORDER BY nome ASC");
$stmt->execute();
$professores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar disciplinas com filtros
$search = $_GET['search'] ?? '';
$professor_filter = $_GET['professor'] ?? '';
$ativo_filter = $_GET['ativo'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(d.nome LIKE ? OR d.descricao LIKE ?)";
    $params[] = "%$search%";
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
    SELECT d.*, u.nome as professor_nome,
           (SELECT COUNT(*) FROM matricula WHERE disciplina_id = d.id) as total_alunos,
           (SELECT COUNT(*) FROM aula WHERE turma_id = d.id) as total_aulas
    FROM disciplina d 
    LEFT JOIN Usuario u ON d.professor_id = u.id 
    $where_clause 
    ORDER BY d.nome ASC
");
$stmt->execute($params);
$disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM disciplina")->fetchColumn(),
    'ativas' => $pdo->query("SELECT COUNT(*) FROM disciplina WHERE ativo = 1")->fetchColumn(),
    'com_professor' => $pdo->query("SELECT COUNT(*) FROM disciplina WHERE professor_id IS NOT NULL")->fetchColumn(),
    'total_alunos' => $pdo->query("SELECT COUNT(*) FROM matricula")->fetchColumn()
];

$username = $_SESSION['username'] ?? 'Coordenador';
?>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="container">
        <!-- Cabeçalho da página -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-book"></i> Gerenciar Disciplinas</h1>
                <p>Administre disciplinas do sistema educacional</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Nova Disciplina
            </button>
        </div>

        <!-- Cards de estatísticas -->
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
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['com_professor']; ?></div>
                    <div class="stat-label">Com Professor</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total_alunos']; ?></div>
                    <div class="stat-label">Total de Matrículas</div>
                </div>
            </div>
        </div>

        <!-- Filtros e busca -->
        <div class="filters-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por nome ou descrição..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group">
                <select id="professorFilter">
                    <option value="">Todos os professores</option>
                    <option value="0" <?php echo $professor_filter === '0' ? 'selected' : ''; ?>>Sem professor</option>
                    <?php foreach ($professores as $professor): ?>
                    <option value="<?php echo $professor['id']; ?>" 
                            <?php echo $professor_filter == $professor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($professor['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="ativoFilter">
                    <option value="">Todos os status</option>
                    <option value="1" <?php echo $ativo_filter === '1' ? 'selected' : ''; ?>>Ativas</option>
                    <option value="0" <?php echo $ativo_filter === '0' ? 'selected' : ''; ?>>Inativas</option>
                </select>
                
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Limpar
                </button>
            </div>
        </div>

        <!-- Tabela de disciplinas -->
        <div class="table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Disciplina</th>
                        <th>Professor</th>
                        <th>Carga Horária</th>
                        <th>Alunos</th>
                        <th>Aulas</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disciplinas as $disciplina): ?>
                    <tr>
                        <td>
                            <div class="disciplina-info">
                                <div class="disciplina-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="disciplina-details">
                                    <div class="disciplina-name"><?php echo htmlspecialchars($disciplina['nome']); ?></div>
                                    <?php if ($disciplina['descricao']): ?>
                                    <div class="disciplina-desc"><?php echo htmlspecialchars($disciplina['descricao']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($disciplina['professor_nome']): ?>
                                <span class="professor-name"><?php echo htmlspecialchars($disciplina['professor_nome']); ?></span>
                            <?php else: ?>
                                <span class="no-professor">Sem professor</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="carga-horaria"><?php echo $disciplina['carga_horaria']; ?>h</span>
                        </td>
                        <td>
                            <span class="count-badge"><?php echo $disciplina['total_alunos']; ?></span>
                        </td>
                        <td>
                            <span class="count-badge"><?php echo $disciplina['total_aulas']; ?></span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $disciplina['ativo'] ? 'active' : 'inactive'; ?>">
                                <?php echo $disciplina['ativo'] ? 'Ativa' : 'Inativa'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="editDisciplina(<?php echo $disciplina['id']; ?>)" 
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteDisciplina(<?php echo $disciplina['id']; ?>)" 
                                        title="Deletar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($disciplinas)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h3>Nenhuma disciplina encontrada</h3>
                <p>Tente ajustar os filtros ou criar uma nova disciplina.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para criar/editar disciplina -->
<div id="disciplinaModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Nova Disciplina</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="disciplinaForm">
            <input type="hidden" id="disciplinaId" name="id">
            <input type="hidden" name="ajax" value="1">
            <input type="hidden" id="formAction" name="action" value="create">
            
            <div class="form-group">
                <label for="nome">Nome da Disciplina *</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" rows="3" placeholder="Descrição opcional da disciplina"></textarea>
            </div>
            
            <div class="form-group">
                <label for="professor_id">Professor</label>
                <select id="professor_id" name="professor_id">
                    <option value="">Selecione um professor...</option>
                    <?php foreach ($professores as $professor): ?>
                    <option value="<?php echo $professor['id']; ?>">
                        <?php echo htmlspecialchars($professor['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="carga_horaria">Carga Horária (horas) *</label>
                <input type="number" id="carga_horaria" name="carga_horaria" min="1" max="1000" required>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="ativo" name="ativo" checked>
                    <span class="checkmark"></span>
                    Disciplina ativa
                </label>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmação para deletar -->
<div id="deleteModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h2>Confirmar Exclusão</h2>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <p>Tem certeza que deseja deletar esta disciplina?</p>
            <p><strong>Esta ação não pode ser desfeita.</strong></p>
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                <i class="fas fa-trash"></i> Deletar
            </button>
        </div>
    </div>
</div>

<script>
let currentDeleteId = null;

// Filtros e busca
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('professorFilter').addEventListener('change', applyFilters);
document.getElementById('ativoFilter').addEventListener('change', applyFilters);

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const professor = document.getElementById('professorFilter').value;
    const ativo = document.getElementById('ativoFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (professor) params.append('professor', professor);
    if (ativo !== '') params.append('ativo', ativo);
    
    window.location.href = 'disciplinas.php?' + params.toString();
}

function clearFilters() {
    window.location.href = 'disciplinas.php';
}

// Modal functions
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nova Disciplina';
    document.getElementById('formAction').value = 'create';
    document.getElementById('disciplinaForm').reset();
    document.getElementById('disciplinaId').value = '';
    document.getElementById('ativo').checked = true;
    document.getElementById('disciplinaModal').style.display = 'flex';
}

function editDisciplina(id) {
    // Buscar dados da disciplina
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
            document.getElementById('modalTitle').textContent = 'Editar Disciplina';
            document.getElementById('formAction').value = 'update';
            document.getElementById('disciplinaId').value = disciplina.id;
            document.getElementById('nome').value = disciplina.nome;
            document.getElementById('descricao').value = disciplina.descricao || '';
            document.getElementById('professor_id').value = disciplina.professor_id || '';
            document.getElementById('carga_horaria').value = disciplina.carga_horaria;
            document.getElementById('ativo').checked = disciplina.ativo == 1;
            document.getElementById('disciplinaModal').style.display = 'flex';
        }
    });
}

function deleteDisciplina(id) {
    currentDeleteId = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('disciplinaModal').style.display = 'none';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentDeleteId = null;
}

function confirmDelete() {
    if (currentDeleteId) {
        fetch('disciplinas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax=1&action=delete&id=${currentDeleteId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage('error', data.message);
            }
            closeDeleteModal();
        });
    }
}

// Form submission
document.getElementById('disciplinaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('disciplinas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('success', data.message);
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage('error', data.message);
        }
    })
    .catch(error => {
        showMessage('error', 'Erro de conexão');
    });
});

// Message system
function showMessage(type, message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message message-${type}`;
    messageDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        messageDiv.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 300);
    }, 3000);
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    const disciplinaModal = document.getElementById('disciplinaModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (e.target === disciplinaModal) {
        closeModal();
    }
    if (e.target === deleteModal) {
        closeDeleteModal();
    }
});
</script>

<style>
/* Estilos específicos para a página de disciplinas */
.disciplina-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.disciplina-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.disciplina-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.disciplina-desc {
    font-size: 0.875rem;
    color: var(--text-secondary);
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.professor-name {
    color: var(--text-primary);
    font-weight: 500;
}

.no-professor {
    color: var(--text-secondary);
    font-style: italic;
}

.carga-horaria {
    font-weight: 600;
    color: var(--primary-color);
}

.count-badge {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--input-bg);
    color: var(--text-primary);
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

/* Reutilizar estilos da página de usuários */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid var(--border-color);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.filters-section {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 300px;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.search-box input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--input-bg);
    color: var(--text-primary);
}

.filter-group {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.filter-group select {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--input-bg);
    color: var(--text-primary);
    min-width: 150px;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-badge.inactive {
    background: #ffebee;
    color: #c62828;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-edit {
    background: #e3f2fd;
    color: #1976d2;
}

.btn-edit:hover {
    background: #bbdefb;
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
    font-size: 4rem;
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

.modal-small {
    max-width: 400px;
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
    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        min-width: auto;
    }
    
    .filter-group {
        flex-wrap: wrap;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

</body>
</html>
